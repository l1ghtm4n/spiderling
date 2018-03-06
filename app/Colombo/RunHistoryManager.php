<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 11/2/17
 * Time: 16:01
 */

namespace App\Colombo;


use App\Browser\SiteMap;
use App\Models\RunHistory;
use App\Models\Site;
use App\Models\Task;
use App\Stack\Drivers\MysqlStack;
use App\Stack\Item;
use Carbon\Carbon;

class RunHistoryManager
{

    /**
     * @var Site
     */
    private $site;
    private $site_map;
    private $stack;
    private $auto_days = 1; // tự động chạy lại 1 ngày

    public function __construct(Site $site)
    {
        $this->site = $site;
        $this->site_map = new SiteMap($site->getJson());
        $site_stack_key = 'site_';
        $this->stack = new MysqlStack($site_stack_key . $site->id);
    }

    public static function makeFromSiteId($site_id)
    {
        $site = Site::findOrFail($site_id);
        return new RunHistoryManager($site);
    }

    /**
     * Check before make history and reset
     */
    public function can_be_reset()
    {
        // Trang thái site ready
        if ($this->site->status != Site::STATUS_READY) {
            if ($this->site->status == Site::STATUS_EDIT) {
                CliEcho::warningnl("Inactive all history of site " . $this->site->id);
                RunHistoryManager::inactiveAllHistory($this->site->id);
            }
            CliEcho::warningnl("Be cause status");
            $this->site->crawl_task()->update(['status' => Task::STATUS_STOP]);
            return false;
        }

        // đã được tạo 2 ngày trước
        if ($this->site->created_at->diffInDays(Carbon::now(), true) < 2) {
            CliEcho::warningnl("Be cause date created");
            return false;
        }
        // đã crawl xong
        if ($this->site->crawl_task && $this->site->crawl_task->status != Task::STATUS_DONE) {
            CliEcho::warningnl("Be cause crawl task");
            return false;
        }
        return true;
    }

    /**
     * Make history
     */
    public function make()
    {
        // kiểm tra xem có history chưa hoàn thành tương ứng với lượt chạy hiện tại
        $history = RunHistory::where('site_id', $this->site->id)
            ->where('status', '!=', RunHistory::STATUS_DONE)
            ->where('attempts', $this->site->crawled_attempts)
            ->first();

        if (!$history) {
            // tạo mới nếu chưa có
            $history = new RunHistory();
            // cập nhật priority từ các history cũ
            $history->priority = $this->computePriority($this->site->id);
            if ($history->priority === false) {
                // ban đầu lấy số lượng tài liệu làm mức ưu tiên
                $history->priority = $this->site->got_data;
            }
            $history->site_id = $this->site->id;
            $history->attempts = $this->site->crawled_attempts;
        }

        // cập nhật thông tin
        $history->crawled_links = $this->site->crawled_links;
        $history->last_crawled = $this->site->crawled_links;
        $history->downloadable_links = $this->site->get_data;
        $history->last_downloadable = $this->site->got_data;
        $history->header_checked = $this->site->header_checked;
        $history->stack_count = $this->stack->count();
        $history->stack_reset_count = 0;

        $stop_conditions = $this->shouldResetNodes();
        $history->stop_conditions = ['reset' => $stop_conditions];

        if (!$history->save()) {
            return false;
        } else {
            return $history;
        }
    }

    public function doResetAction()
    {
        if (!$this->can_be_reset()) {
            throw new \Exception("Can not reset this site");
        }
        $history = RunHistory::where('site_id', $this->site->id)
            ->where('status', 0)
            ->where('attempts', $this->site->crawled_attempts)
            ->first();

        if ($history) {
            $except = array_get($history->stop_conditions, 'reset', []);
            \DB::beginTransaction();
            // Tăng attempts cho Site
            $history->site()->increment('crawled_attempts', 1);
            // Tăng enable task và tăng attempt
            $history->site
                ->crawl_task
                ->update([
                    'status' => Task::STATUS_WAIT_RERUN,
                    'restart_at' => Carbon::now(),
                    'attempts' => \DB::raw("attempts + 1"),
                    'priority' => $history->priority,
                ]);

            $reset_count = $this->stack->resetState($except);
            // Đổi trạng thái cho history
            \DB::commit();
            $history->stack_reset_count = $reset_count;
            $history->status = 1;
            return $history->save();
        }
    }

    /**
     * B
     * @return array
     */
    private function shouldResetNodes()
    {
        $loop_nodes = $this->site_map->loopNodes();
        $except_nodes = [];

        foreach ($loop_nodes as $loop_node) {
            $children = $this->site_map->getChildren($loop_node['id'], 'link');
            if (count($children) < 2) {
                $except_nodes[] = $loop_node['id'];
            }
            foreach ($children as $child) {
                if ($child['id'] == $loop_node['id']) {
                    continue;
                } else {
                    $except_nodes[] = $child['id'];
                }
            }
        }

        return $this->site_map->nodeNameList($except_nodes, config('config_job.should_reset_nodes'));
    }

    public function afterCrawled($done = true)
    {
        // Update run history hiện tại
        $data = [
            'last_crawled' => $this->site->crawled_links,
            'last_downloadable' => $this->site->got_data,
        ];
        if ($done) {
            // cập nhật trang thái
            $data['status'] = RunHistory::STATUS_DONE;
        }
        $this->site->last_history()->update($data);
        if ($done && $data['priority'] < 1) {
            $this->site->status = Site::STATUS_EDIT;
            $this->site->save();
        }
    }

    /**
     * Tính toán độ ưu tiên của hệ thống
     * @param $site_id
     * @return bool|float|int
     */
    public function computePriority($site_id)
    {
        $histories = RunHistory::where('site_id', $site_id)->orderBy('attempts', 'desc')->take(7)->get();
        if ($histories->count() == 0) {
            return false;
        }
        $sum_added = $histories->sum(function ($item) {
            /** @var RunHistory $item */
            return $item->last_downloadable > $item->downloadable_links ? $item->last_downloadable - $item->downloadable_links : 0;
        });
        if ($sum_added < 1 && $histories->count() > 5) {
            return -1; // dừng ko chạy nữa, chuyển sang trạng thái need edit
        }
        $priority = ceil($sum_added * 7 / $histories->count());
        return $priority;
    }

    public function markHistoryAsDone()
    {
        $history = RunHistory::where('site_id', $this->site->id)
            ->where('attempts', $this->site->crawled_attempts - 1)->first();

        if ($history) {
            $history->status = RunHistory::STATUS_DONE;
            $history->last_crawled = $this->site->crawled_links;
            $history->last_downloadable = $this->site->got_data;
            $history->last_run = Carbon::now();
            return $history->save();
        } else {
            return null;
        }
    }

    public static function clearHistory($site_id)
    {
        // only apply for deleted site
        if (!Site::find($site_id)) {
            RunHistory::where('site_id', $site_id)->delete();
        }
    }

    public static function inactiveAllHistory($site_id)
    {
        // only apply for no ready site
        if (!Site::where('id', $site_id)->where('status', Site::STATUS_READY)->first()) {
            RunHistory::where('site_id', $site_id)->update(['status' => RunHistory::STATUS_INACTIVE]);
        }
    }
}

/**
 * [
 * "id" => "detail"
 * "title" => "Detail"
 * "type" => "link"
 * "test_url" => "http://ainamulyana.blogspot.com/"
 * "parent_selectors" => array:2 [
 * 0 => "root"
 * 1 => "older-post"
 * ]
 * "selector" => "css:  h2.post-title.entry-title > a"
 * "multiple" => true
 * ]
 */