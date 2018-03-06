<?php
 Route::get('company', function() {
 	\App\Colombo\CliEcho::enable_flush(true);
     echo "<div style='background:#ccc;font-family: monospace;padding:5px;'>";
     $driver = [
         'phantomjs'=> [
             'name' => 'phantomjs',
             'server' => env('PHANTOM_TEST_SERVER','http://localhost'),
             'port' => env('PHANTOM_TEST_PORT', 4445)
         ]
     ];
     $crawler = new \App\SmallCrawler\Crawler($driver['phantomjs']);
     $crawler->crawler();
     echo "</div>";
 });