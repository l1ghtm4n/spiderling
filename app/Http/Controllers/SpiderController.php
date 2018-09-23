<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Openbuildings\Spiderling\Page;

class SpiderController extends Controller
{
    public function index()
    {
        $x = 361;

        $page = new Page();
        for ($i = 21; $i <= 36; $i++) { // 36
            $page->visit('http://diaoconline.vn/du-an/khu-dan-cu-do-thi-moi-c24/' . $i);
            //            $dom1 = $page->all('div.invest_content > h2');
            $dom2 = $page->all('.location');
            foreach ($dom2 as $item) {
                //                $name = trim($item->text());
                $location = trim(str_replace(["Vị trí: "], "", $item->text()));
                DB::table('diaoconline')->where('id', $x++)->update([
                    'location' => $location,
                ]);
                dump($x . $location);
            }
        }
    }

    public function getLink()
    {
        $totalPage = 75489;
        $link = 'http://masocongty.vn/search?name=&by=&pro=0&page=';
        $page = new Page();
        $i = 0;
        try {
            for ($i; $i <= 1999; $i++) {
                sleep(2);
                $page->visit($link . $i);
                $dom = $page->all('div.listview-outlook > a');

                foreach ($dom as $x => $item) {
                    DB::table('topcv_links')->insert([
                        'link' => $item->attribute('href'),
                    ]);
                    echo "Insert success " . $x . "\n";
                }
                echo "-----------------Insert success trang " . $i . "\n";
            }
        } catch (\Exception $e) {
            echo "Loi: " . $e . "\n";
            echo "Lay thanh cong den trang: " . $i . "\n";
        }
    }

    public function getContent()
    {
        $links = DB::table('vunhat_link')->where('id', 390)->pluck('link');
        foreach ($links as $i => $link) {
            $page = new Page();
            $page->visit($link);
            $thumb = [];
            $name = $page->find('.product-name');
            $price = $page->find('.product-list li:nth-child(1) span');
            $sub_description = $page->find('.product-color-filter-container');
            $description = $page->find('#description');
            $type = $page->find('.breadcrumb li:nth-child(3)');
            $supplier = $page->find('.breadcrumb li:nth-child(4)');
            $image = $page->all('.product-viewer img');
            foreach ($image as $item) {
                array_push($thumb, $item->attribute('src'));
            }
            $thumb = implode('|', $thumb);


            echo "Ảnh: " . $thumb . "</br>";
            echo "Tên sản phẩm: " . $name->text() . "</br>";
            echo "Giá: " . $price->text() . "</br>";
            echo "Khuyến mại: " . $sub_description->html() . "</br>";
            echo "Mô tả chi tiết: " . $description->html() . "</br>";
            echo "Loại: " . $type->text() . "</br>";
            echo "Nhà sản xuất: " . $supplier->text() . "</br>";
            die;
            try {
                $id = DB::table('vunhat_img')->insertGetId([
                    'link' => $thumb,
                ]);

                DB::table('products')->insert([
                    'name'            => $name->text(),
                    'price'           => $price->text(),
                    'sub_description' => $sub_description->html(),
                    'description'     => $description->html(),
                    'type'            => $type->text(),
                    'supplier'        => $supplier->text(),
                    'id_image'        => $id,
                ]);
            } catch (\Exception $exception) {
                dd($exception);
                DB::table('vunhat_error')->insert([
                    'link' => $link,
                ]);
            }

            //echo "Ảnh: " . $thumb . "</br>";
            //echo "Tên sản phẩm: " . $name->text() . "</br>";
            //echo "Giá: " . $price->text() . "</br>";
            //echo "Khuyến mại: " . $sub_description->html() . "</br>";
            //echo "Mô tả chi tiết: " . $description->html() . "</br>";
            //echo "Loại: " . $type->text() . "</br>";
            //echo "Nhà sản xuất: " . $supplier->text() . "</br>";
            echo "Insert success " . $i . "\n";
        }
    }

    public function show()
    {
        $page = new Page();
        $page->visit('https://cafeland.vn/du-an/khu-dan-cu/page-12/');

        $li = $page->all('div.page-content > div:nth-child(2) > ul > li');
        foreach ($li as $item) {
            $name = $item->find('h3')->text();
            $link = $item->find('a')->attribute('href');
            dump($link);
        }

    }

    public function edit()
    {
        $links = DB::table('links')->skip(202)->take(20)->get();
        foreach ($links as $item) {
            $page->visit($item->name);
            sleep(1);
            $dom1 = $page->find('table > tbody > tr:nth-child(2) > td:nth-child(1)');
            $dom2 = $page->find('table > tbody > tr:nth-child(4) > td:nth-child(2)');
            $location = trim(str_replace(["Vị trí: ", "Vị trí : "], "", $dom1->text()));
            $complate = trim(str_replace(["Năm hoàn thành:", "Năm hoàn thành: "], "", $dom2->text()));
            DB::table('urbans')->where('id', $item->id)->update([
                'address' => $location,
                'status'  => $complate,
            ]);
            dump($location);
        }
    }

    public function getContents()
    {
        //$page = new Page();
        //$page->visit('https://thongtindoanhnghiep.co/0312477581-cong-ty-tnhh-my-pham-sang-hong-nhat-nhat');
        //$dom2 = $page->find('h1');
        //dump($dom2->text());

        $data = file_get_html('https://thongtindoanhnghiep.co/0100102608-011-cn-tan-duong-cua-tong-cty-lt-mien-bac-tai-tinh-dong-thap');
        $masothue = $data->find('h3 a strong');
        $ngaycap = $data->find('tbody tr:nth-child(1) td');
        $title = $data->find('h1');
        $diachicongty = $data->find('tr:nth-child(4) td[colspan=5] h3');
        $tinhthanhtitle = trim($diachicongty[0]->plaintext);
        preg_match_all('/(.[^\-]*?)$/miu', $tinhthanhtitle, $tinhthanh);
        $nganhnghetitle = $data->find('table:nth-child(3) tbody tr:nth-child(15) td a');
        echo "Ma so thue: " . $masothue[0]->plaintext . "</br>";
        echo "Ngay cap: " . $ngaycap[1]->plaintext . "</br>";
        echo "Title: " . $title[0]->plaintext . "</br>";
        echo "Dia chi cong ty: " . $diachicongty[0]->plaintext . "</br>";
        echo "Tinh thanh: " . str_replace('-', '', implode('', $tinhthanh[0])) . "</br>";
        echo "Nganh nghe title: " . $nganhnghetitle[2]->plaintext . "</br>";
    }

    public function getContentTVN($start = 0, $num = 500)
    {
        $links = \DB::table('timviecnhanh_links')->skip($start)->take($num)->get(); //Lấy bắt đầu từ bản ghi thứ 11 và lấy 10 bản ghi
        $page = new Page();
        foreach ($links as $i => $val) {
            try {
                $page->visit($val->link);

                $selectors = [
                    [
                        'field' => 'name',
                        'dom'   => 'h1 > span.text',
                    ],
                    [
                        'field' => 'address',
                        'dom'   => '.summay-company > p:nth-child(1)',
                    ],
                    [
                        'field' => 'web',
                        'dom'   => '.summay-company > p:nth-child(2)',
                    ],
                    [
                        'field' => 'description',
                        'dom'   => '.summay-company > p:nth-child(3)',
                    ],
                    [
                        'field'     => 'thumb',
                        'dom'       => 'div.col-xs-2.offset20.push-right.no-padding > a > img',
                        'attribute' => 'src',
                        'check'     => 'thumb',
                    ],
                    [
                        'field'     => 'status',
                        'dom'       => 'div.col-xs-2.offset20.push-left-20 > img',
                        'attribute' => 'alt',
                        'check'     => 'status',
                    ],
                ];
                $data = [];
                foreach ($selectors as $item) {
                    $dom = $page->find($item['dom']);
                    if (!isset($item['attribute'])) {
                        $data[$item['field']] = $dom->text();
                        $data[$item['field']] = trim(str_replace(['Địa chỉ:', 'Website:'], '', $data[$item['field']]));
                    }
                    else if (isset($item['attribute'])) {
                        $data[$item['field']] = $dom->attribute($item['attribute']);
                        if (isset($item['check']) && $item['check'] == 'status') {
                            $data[$item['field']] = $data[$item['field']] == 'Tài khoản xác thực' ? 1 : 0;
                        }
                        else {
                            $data[$item['field']] = $data[$item['field']] == 'https://cdn.timviecnhanh.com/asset/home/img/default.gif' ? '' : $data[$item['field']];
                        }
                    }
                }
                $data['tvn_link_id'] = $val->id;

                \DB::table('timviecnhanh_contents')->insert($data);
                echo "Insert success data from link " . ($i + 1) . "\n";
            } catch (\Exception $e) {
                \DB::table('timviecnhanh_links')->where('id', $val->id)->update([
                    'status' => 0,
                ]);
                echo "Update status vi loi " . ($i + 1) . "\n";
            }
        }
    }

    public function getLink84($link = 'http://www.hotel84.com/resort/')
    {
        $pageFirst = new Page();
        $pageFirst->visit($link);
        $domAllLinkCity = $pageFirst->all('a.tbold.tblack');

        foreach ($domAllLinkCity as $x => $val) {
            $pageSecond = new Page();
            $pageSecond->visit($val->attribute('href'));
            try {
                $numPage = $pageSecond->find('.div_page_list a:nth-last-child(2)');
                $check = 1;
            } catch (\Exception $e) {
                $check = 0;
            }

            if ($check == 1) {
                for ($i = 1; $i <= $numPage->text(); $i++) {
                    $page = new Page();
                    $page->visit($val->attribute('href') . 'page' . $i . '/');
                    $domKs = $page->all('a.tbold.tblue');
                    foreach ($domKs as $ks) {
                        //dump($ks->attribute('href'));
                        \DB::table('hotel84_links')->insert([
                            'link' => $ks->attribute('href'),
                        ]);
                        echo $ks->attribute('href') . "\n";
                    }
                }
            }
            else {
                $page = new Page();
                $page->visit($val->attribute('href') . '/');
                $domKs = $page->all('a.tbold.tblue');
                foreach ($domKs as $ks) {
                    //dump($ks->attribute('href'));
                    \DB::table('hotel84_links')->insert([
                        'link' => $ks->attribute('href'),
                    ]);
                    echo $ks->attribute('href') . "\n";
                }
            }
        }
    }

    public function getContent84($start = 0, $num = 500)
    {
        $links = \DB::table('hotel84_links')->skip($start)->take($num)->get(); //Lấy bắt đầu từ bản ghi thứ 11 và lấy 10 bản ghi
        //$links = \DB::table('timviecnhanh_links')->where('id', 112)->get(); //Lấy bắt đầu từ bản ghi thứ 11 và lấy 10 bản ghi
        //$links = ['http://www.hotel84.com/tp-ho-chi-minh/khach-san-khai-hoan.html'];
        $page = new Page();
        foreach ($links as $i => $val) {
            try {
                $page->visit($val->link);
                $selectors = [
                    [
                        'field' => 'name',
                        'dom'   => 'h2.title_cruise_detail',
                    ],
                    [
                        'field' => 'rate',
                        'dom'   => 'div.div_cruise_detail > img',
                        'getBy' => 'src',
                    ],
                    [
                        'field' => 'address',
                        'dom'   => '//div[@class="top_right_cruise"]/span[@class="tbold"][1]//following::text()[1]',
                        'type'  => 'xpath',
                    ],
                    [
                        'field' => 'phone',
                        'dom'   => '//div[@class="top_right_cruise"]/span[@class="tbold"][2]//following::text()[1]',
                        'type'  => 'xpath',
                    ],
                    [
                        'field' => 'email',
                        'dom'   => '//div[@class="top_right_cruise"]/span[@class="tbold"][3]//following::text()[1]',
                        'type'  => 'xpath',
                    ],
                    [
                        'field' => 'web',
                        'dom'   => '//div[@class="top_right_cruise"]/span[@class="tbold"][4]//following::text()[1]',
                        'type'  => 'xpath',
                    ],
                    [
                        'field' => 'num_of_room',
                        'dom'   => '//div[@class="top_right_cruise"]/span[@class="tbold"][5]//following::text()[1]',
                        'type'  => 'xpath',
                    ],
                    [
                        'field' => 'picture',
                        'dom'   => 'li.li_thumbs > a > img',
                        'type'  => 'all',
                        'getBy' => 'all',
                    ],
                    [
                        'field' => 'description',
                        'dom'   => '.summary_cruise',
                        'getBy' => 'html',
                    ],
                    [
                        'field' => 'room_type',
                        'dom'   => '#item_index1',
                        'getBy' => 'html',
                    ],
                    [
                        'field' => 'room_price',
                        'dom'   => '#item_rate',
                        'getBy' => 'html',
                    ],
                    [
                        'field' => 'room_convenient',
                        'dom'   => '#item_service',
                        'getBy' => 'html',
                    ],
                    [
                        'field' => 'room_service',
                        'dom'   => '#item_restaurant',
                        'getBy' => 'html',
                    ],
                    [
                        'field' => 'room_location',
                        'dom'   => '#item_location',
                        'getBy' => 'html',
                    ],
                ];
                $data = [];
                foreach ($selectors as $item) {
                    if (isset($item['type']) && $item['type'] == 'xpath') {
                        try {
                            $dom = $page->find([$item['type'], $item['dom']]); //Tìm thông tin chung
                        } catch (\Exception $e) {
                            $data[$item['field']] = null;
                            continue;
                        }
                    }
                    else if (isset($item['type']) && $item['type'] == 'all') {
                        $dom = $page->all($item['dom']); //Tìm ảnh
                    }
                    else {
                        try {
                            $dom = $page->find($item['dom']);
                        } catch (\Exception $e) {
                            $data[$item['field']] = null;
                            continue;
                        }
                    }
                    if ($item['field'] != null) {
                        if (isset($item['getBy'])) {
                            if ($item['getBy'] == 'src') {
                                $data[$item['field']] = $dom->attribute('src');
                            }
                            else if ($item['getBy'] == 'html') {
                                $data[$item['field']] = $dom->html();
                            }
                            else if ($item['getBy'] == 'all') {
                                $picture = [];
                                foreach ($dom as $pic) {
                                    array_push($picture, 'http://www.hotel84.com' . $pic->attribute('src'));
                                }
                                $data[$item['field']] = implode('|', $picture);
                            }
                        }
                        else {
                            $data[$item['field']] = $dom->text();
                        }
                        if ($item['field'] == 'web') {
                            $data[$item['field']] = 'http://www.' . $data[$item['field']];
                        }
                        if ($item['field'] == 'rate') {
                            preg_match_all('/[0-9](?=_stars)/', $data[$item['field']], $match);
                            $data[$item['field']] = isset($match[0][0]) ? $match[0][0] : null;
                        }
                    }
                }
                $data['type'] = 2;
                $data['84_link_id'] = $val->id;
                \DB::table('hotel84_contents')->insert($data);
                echo "Insert success data from link " . ($i + 1) . "\n";
            } catch (\Exception $e) {
                \DB::table('hotel84_links')->where('id', $val->id)->update([
                    'status' => 0,
                ]);
                echo "Update status vi loi " . ($i + 1) . "\n";
            }
        }
    }

    public function getContentViVu()
    {
        $link = 'public/vivu/link.html';
        $page = new Page();
        $page->find();

        $selectors = [
            [
                'field' => 'name',
                'dom'   => '',
            ],
            [
                'field' => 'rate',
                'dom'   => '',
            ],
            [
                'field' => 'address',
                'dom'   => '',
            ],
            [
                'field' => 'thumb',
                'dom'   => '',
            ],
            [
                'field' => 'name',
                'dom'   => '',
            ],
        ];
    }

    public function getContentVnTrip($start = 1, $num = 500)
    {
        $page = new Page();
        //for ($i = $start; $i <= $num; $i++) {
        //    try{
        //$link = 'https://www.vntrip.vn/khach-san/' . $i;
        $link = 'https://khachsan.chudu24.com/t.ho-chi-minh.html?page=7';
        $page->visit($link);
        $selectors = [
            [
                'field' => 'name',
                'dom'   => 'div.detail-title > h1',
            ],
            [
                'field' => 'address',
                'dom'   => 'p > span.address',
            ],
            [
                'field' => 'rate',
                'dom'   => '.detail-title div.hotel-rating > span.text-start > i',
            ],
            [
                'field' => 'web',
                'dom'   => 'h2.title_cruise_detail',
            ],
            [
                'field' => 'price',
                'dom'   => '#tbl-rate-0 tr:nth-child(1) td.pt-price > p.new-price',
            ],
            [
                'field' => 'thumb',
                'dom'   => '.detail-slider-fancy div:nth-child(1) > a > img',
                'getBy' => 'src'
            ],
            [
                'field' => 'description',
                'dom'   => 'div.content-description > p.full',
                'geyBy' => 'html'
            ],
        ];
        $dom = $page->all('h2 > a');
        foreach ($dom as $item) {
            dump($item->attribute('href'));
        }
        //}catch(\Exception $e){
        //
        //}

        //}
    }

    public function getCategory(){
        $page = new Page();
        $page->visit('https://itsolutionstuff.com/tags');
        $dom = $page->all(['xpath', "//span[@class='label label-info']"]);
        foreach ($dom as $item) {
            dump($item->text());

        }
    }
}
