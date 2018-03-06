<?php

namespace App\SmallCrawler;

use App\Helpers\PhpUri;
use Openbuildings\Spiderling\Driver_Phantomjs;
use Openbuildings\Spiderling\Driver_Phantomjs_Connection;
use Openbuildings\Spiderling\Driver_Selenium;
use Openbuildings\Spiderling\Page;


/**
 * Created by PhpStorm.
 * User: Hungokata
 * Date: 1/25/18
 * Time: 11:59 AM
 */
class Crawler
{
    public $delay = 2;
    public $vietnamString = [
        "Ph", "th", "tr", "gi", "ch", "nh", "ng", "kh", "gh", "a", "ă", "â", "b", "c", "d", "đ", "e", "ê",
        "g", "h", "i", "k", "l", "m", "n", "o", "ô", "ơ", "p", "q", "r", "s", "t", "u", "ư", "v", "x", "y",
    ];

    const TABLE_NAME = 'skills';
    const FIELD_SEARCH = 'sk_name';
    const FIELD_STATUS = 'sk_status';
    const STATUS_INIT = 0;
    const STATUS_DONE = 1;

    public function __construct($driver = [])
    {
        $this->client = $this->initBrowser($driver);
    }

    /**
     * init browser
     * @param $driver
     * @return Page
     */
    public function initBrowser($driver)
    {
        switch ($driver['name']) {
            case 'phantomjs':
                $phantomjs_driver_connection = new Driver_Phantomjs_Connection($driver['server']);
                $phantomjs_driver_connection->port($driver['port']);
                $phantomjs_driver = new Driver_Phantomjs();
                $phantomjs_driver->connection($phantomjs_driver_connection);
                $page = new Page($phantomjs_driver);
                break;

            case 'selenium':
                $connection = new \Openbuildings\Spiderling\Driver_Selenium_Connection($driver['server']);
                $desiredCapabilities = array_get($driver, 'desiredCapabilities', [
                    'browserName'    => 'chrome',
                    'acceptSslCerts' => true,
                    "chromeOptions"  => [
                        "args" => [
                            '--disable-gpu',
                            '--headless',
                        ],
                    ],
                ]);
                try {
                    if (!$connection->reuse_session()) {
                        $connection->new_session($desiredCapabilities);
                    }
                } catch (\ErrorException $ex) {

                }
                $connection->start($desiredCapabilities);
                $selenium_driver = new Driver_Selenium();
                $selenium_driver->connection($connection);
                $page = new Page($selenium_driver);
                break;
        }

        return $page;
    }
    /**
     * Login crawler
     */
    public function crawlerLogin(){
        $this->visit("https://www.topcv.vn/login", [], 2);
        $this->client->find('input[name="email"]')->set('familymaokhe@gmail.com');
        $this->client->find('input[name="password"]')->set('huongmk1234');
        $this->client->find('input[type="submit"]')->click();

        echo "\nDang nhap thanh cong\n";
    }
    /**
     * Run crawler
     */
    public function crawler($num_skip)
    {
        set_time_limit(10000);

        $topcv = \DB::table('topcv_links')->skip($num_skip)->take(1000)->get();
        $sl = 0;
        try{
            foreach ($topcv as $i => $val) {
                $info = [];
                $this->visit($val->link, [], 2);

                $selectors = [
                    [
                        'field'    => 'name',
                        'selector' => '//*[@id="company-title"]/div[2]/div/div[1]/p',
                        'multiple' => false,
                    ],
                    [
                        'field'    => 'about',
                        'selector' => '//*[@id="company-about"]/div',
                        'multiple' => false,
                    ],
                    [
                        'field0'   => 'phone',
                        'field1'   => 'web',
                        'field2'   => 'num_member',
                        'selector' => '//*[@id="company-title"]/div[2]/div/div[1]/div/div',
                        'multiple' => true,
                    ],
                    [
                        'field'    => 'thumb',
                        'selector' => '//*[@id="company-title"]/div[1]/a/img',
                        'multiple' => false,
                        'type'     => 'src',
                    ],

                ];

                foreach ($selectors as $key => $item) {
                    if ($item['multiple'] == true) {
                        $node = $this->els(['xpath', $item['selector']]);
                    }
                    else {
                        $node = [$this->el(['xpath', $item['selector']])];
                    }

                    foreach ($node as $mynode) {
                        if ($mynode) {
                            if (isset($item['type']) && $item['type'] == 'src') {
                                $info[$item['field']] = $mynode->attribute('src');
                            }
                            else if ($item['multiple'] == true) {
                                if (count($node) == 2) {
                                    $info[$item['field0']] = $node[0]->text();
                                    if (strpos($node[1]->text(), 'http') !== false || strpos($node[1]->text(), 'www') !== false) {
                                        $info[$item['field1']] = $node[1]->text();
                                    }
                                    else {
                                        $info[$item['field2']] = $node[1]->text();
                                    }
                                    break;
                                }
                                else if (count($node) == 3) {
                                    $info[$item['field0']] = $node[0]->text();
                                    $info[$item['field1']] = $node[1]->text();
                                    $info[$item['field2']] = $node[2]->text();
                                    break;
                                }
                                else {
                                    $info[$item['field1']] = $node[0]->text();
                                }
                            }
                            else {
                                if ($item['field'] == 'about') {
                                    $info[$item['field']] = $mynode->html();
                                }
                                else {
                                    $info[$item['field']] = $mynode->text();
                                }
                            }
                        }
                    }
                }
                //dd($info);
                //Insert du lieu
                \DB::table('topcv_links')->where('id', $val->id)->update([
                    'name'       => $info['name'],
                    'about'      => isset($info['about']) ? $info['about'] : '',
                    'phone'      => isset($info['phone']) ? $info['phone'] : '',
                    'web'        => isset($info['web']) ? $info['web'] : '',
                    'num_member' => isset($info['num_member']) ? $info['num_member'] : '',
                    'thumb'      => isset($info['thumb']) ? $info['thumb'] : '',
                ]);
                $sl++;
                echo "Lay du lieu thanh cong id " . $val->id . "\n";
            }
        }
        catch(\Exception $e){
            echo "Mat ket noi toi phantomJS\n";
            echo "Tong so ban ghi clone: ".$sl;
        }
    }


    /**
     * Find all element
     * @param $selector
     * @return array|\Openbuildings\Spiderling\Nodelist
     */
    public
    function els($selector)
    {
        try {
            $el = $this->client->all($selector);
        } catch (\Exception $ex) {
            $el = [];
        }

        return $el;
    }

    /**
     * Find all element
     * @param $selector
     * @return array|\Openbuildings\Spiderling\Nodelist
     */
    public
    function el($selector)
    {
        try {
            $el = $this->client->find($selector);
        } catch (\Exception $ex) {
            $el = null;
        }

        return $el;
    }

    /**
     * Visit to website
     * @param        $url
     * @param array  $query
     * @param string $delay
     * @return bool
     */
    public
    function visit($url, $query = [], $delay = '')
    {
        $delay = $delay === '' ? $this->delay : intval($delay);
        if (strpos($url, "//") === false) {
            $url = PhpUri::parse($this->client->current_url())->join($url);
        }
        try {
            $this->client->visit($url, $query);
        } catch (\Exception $ex) {
            dump("Crawler visit " . $url . " error :: " . $ex->getMessage());

            return false;
        }
        $this->client->wait($delay * 1000);

        return true;
    }


    // /***
    //  * Nhặt một item
    //  * @return \Illuminate\Database\Eloquent\Model|null|static
    //  */
    // public function popItem()
    // {
    //     $item = \DB::table(self::TABLE_NAME)->where(self::FIELD_STATUS, self::STATUS_INIT)->orderByDesc('id')->first();
    //     if ($item)
    //     {
    //         \DB::table(self::TABLE_NAME)->where('id', $item->id)->update([
    //             self::FIELD_STATUS => self::STATUS_DONE
    //         ]);
    //     }
    //     return $item;
    // }
}