<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/curl', 'CurlController@curl');

Route::get('/spider', 'SpiderController@index');
Route::get('/getcontent', 'SpiderController@getContentVnTrip');

Route::get('/getjson', 'GetJsonController@getContentVivu');

Route::get('/getcompanylink', 'GetXmlController@getViaLink');
Route::get('/getcompanyfile', 'GetXmlController@getViaFile');


Route::get('/getlink', 'SpiderController@getLink84');
Route::get('/getcontenttopcv', 'SpiderController@getContentTopcv');

Route::get('/getrabbit', 'RabbitController@crawl');

Route::get('/getcate', 'SpiderController@getCategory');

Route::get('/getbyphantom', function (){
    \App\Colombo\CliEcho::enable_flush(true);
    echo "<div style='background:#ccc;font-family: monospace;padding:5px;'>";
    $driver = [
        'phantomjs' => [
            'name'   => 'phantomjs',
            'server' => env('PHANTOM_TEST_SERVER', 'http://localhost'),
            'port'   => env('PHANTOM_TEST_PORT', 4445)
        ]
    ];
    $crawler = new \App\SmallCrawler\Crawler($driver['phantomjs']);
    $crawler->crawler();
    //$crawler->crawlerLogin();
    echo "</div>";
});
Route::get('/getbyselenium', function () {
    \App\Colombo\CliEcho::enable_flush(true);
    echo "<div style='background:#ccc;font-family: monospace;padding:5px;'>";
    $driver = [
        'selenium' => [
            'name'   => 'selenium',
            'server' => 'http://127.0.0.1:4444/wd/hub/',
            'engine' => 'phantomjs',
        ],
    ];
    $crawler = new \App\SmallCrawler\Crawler($driver['selenium']);
    $crawler->crawler();
    echo "</div>";
});
Route::get('/login', function () {
    \App\Colombo\CliEcho::enable_flush(true);
    echo "<div style='background:#ccc;font-family: monospace;padding:5px;'>";
    $driver = [
        'selenium' => [
            'name'   => 'selenium',
            'server' => 'http://127.0.0.1:4444/wd/hub/',
            'engine' => 'phantomjs',
        ],
    ];
    $crawler = new \App\SmallCrawler\Crawler($driver['selenium']);
    $crawler->crawlerLogin();
    echo "</div>";
});

