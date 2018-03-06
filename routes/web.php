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
Route::get('/spider', 'SpiderController@index');
Route::get('/getcontent', 'SpiderController@getContent');

Route::get('/getjson', 'GetJsonController@index');
Route::get('/getcompany', 'GetXmlController@index');


Route::get('/getlink', 'SpiderController@getLink');
Route::get('/getcontenttopcv', 'SpiderController@getContentTopcv');

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
    echo "</div>";
});

