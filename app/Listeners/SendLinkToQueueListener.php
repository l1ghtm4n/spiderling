<?php

namespace App\Listeners;

use App\Events\CrawlRabbitEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Openbuildings\Spiderling\Page;

class SendLinkToQueueListener implements ShouldQueue
{
    public $link;
    public $selectors;
    use InteractsWithQueue;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct($link, $selectors)
    {
        $this->link = $link;
        $this->selectors = $selectors;
    }

    /**
     * Handle the event.
     *
     * @param  CrawlRabbitEvent $event
     * @return void
     */
    public function handle(CrawlRabbitEvent $event)
    {
        $page = new Page();
        $page->visit($this->link);
        $data = [];
        foreach ($this->selectors as $item) {
            try {
                if (isset($item['findBy'])) {
                    $dom = $page->all($item['dom']);
                }
                else {
                    $dom = $page->find($item['dom']);
                }
            } catch (\Exception $e) {
                $data[$item['field']] = null;
            }
            if ($item['field'] != null) {
                if (isset($item['getBy']) && $item['getBy'] == 'attribute') {
                    if ($item['attribute'] == 'alt') {
                        $data[$item['field']] = $dom->attribute($item['attribute']);
                    }
                    else if ($item['attribute'] == 'src') {
                        $data[$item['field']] = [];
                        foreach ($dom as $val) {
                            array_push($data[$item['field']], $val->attribute($item['attribute']));
                        }
                        $data[$item['field']] = implode('|', $data[$item['field']]);
                    }
                    else {
                        $data[$item['field']] = $dom->html();
                    }
                }
                else {
                    $data[$item['field']] = $dom->text();
                }
            }

            \DB::table('dhht_contents')->insert($data);
        }
    }
}
