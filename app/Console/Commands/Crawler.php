<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Crawler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:run {num}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawler ';

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
        \App\Colombo\CliEcho::enable_flush(true);
        //echo "<div style='background:#ccc;font-family: monospace;padding:5px;'>";
        $driver = [
            'phantomjs' => [
                'name'   => 'phantomjs',
                'server' => env('PHANTOM_TEST_SERVER', 'http://localhost'),
                'port'   => env('PHANTOM_TEST_PORT', 4445)
            ]
        ];
        $crawler = new \App\SmallCrawler\Crawler($driver['phantomjs']);

        $crawler->crawler($this->argument('num'));
        //echo "</div>";
    }
}
