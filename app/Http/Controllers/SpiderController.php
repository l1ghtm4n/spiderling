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

    public function getContentTopcv()
    {
        $link = 'https://www.topcv.vn/cong-ty/fpt-software/3.html';
        $page = new Page();
        $page->visit($link);

        $tencongty = $page->find('.company-name');
        $diachi = $page->find('div.col-md-4 div:nth-child(4) p');
        $gioithieu = $page->find('.text-dark-gray');
        $sdt = $page->all('.company-overview .detail-item');
        $sonv = $page->find('div.company-overview');
        $thumb = $page->find('div.company-avatar a img');
        dd($sdt);
        foreach ($sdt as $item) {
            $item->html();
        }
        die;

        echo "Ten cong ty: " . $tencongty->text() . "</br>";
        echo "Dia chi: " . $diachi->text() . "</br>";
        echo "Gioi thieu: " . $gioithieu->text() . "</br>";
        echo "So dien thoai: " . $sdt->text() . "</br>";
        echo "So nhan vien: " . $sonv->text() . "</br>";
        echo "Avatar: " . $thumb->attribute('src') . "</br>";
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

    public function getContent()
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


}
