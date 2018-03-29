<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Openbuildings\Spiderling\Page;

class CrawlerGetLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:getlink {num}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get link nguon';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $totalPage = 75441;
        $link = 'http://masocongty.vn/search?name=&by=&pro=0&page=';
        $page = new Page();
        $i = $this->argument('num');
        try {
            for ($i; $i <= 70000; $i++) {
                $page->visit($link . $i)->wait(2000);
                $dom = $page->all('div.listview-outlook > a');

                foreach ($dom as $x => $item) {
                    DB::table('masocongty_links')->insert([
                        'link' => $item->attribute('href'),
                    ]);
                    echo "Insert success " . $x . "\n";
                }
                echo "-------Xong trang " . $i . "\n";
            }
        } catch (\Exception $e) {
            echo "Lay loi o trang: " . $i . "\n";
        }
    }
}
