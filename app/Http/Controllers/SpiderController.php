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

        $data = file_get_html('https://thongtindoanhnghiep.co/0312477581-cong-ty-tnhh-my-pham-sang-hong-nhat-nhat');
        $masothue = $data->find('h3 a strong');
        $ngaycap = $data->find('tbody tr:nth-child(1) td');
        $title = $data->find('h1');
        $diachicongty = $data->find('tr:nth-child(4) td[colspan=5] h3');
        $nganhnghetitle = $data->find('table:nth-child(3) tbody tr:nth-child(15) td a');
        foreach ($masothue as $item) {
            //echo "Ma so thue: " . $item->plaintext . "</br>";
        }
        foreach ($ngaycap as $i => $item) {
            if ($i == 1) {
                echo "Ngay cap: " . $item->plaintext . "</br>";
            }
        }
        foreach ($diachicongty as $item) {
            echo "Dia chi cong ty: " . $item->plaintext . "</br>";
            $hihi = trim($item->plaintext);
            preg_match_all('/(.[^\-]*?)$/miu', $hihi, $tinhthanh);

            echo "Tinh thanh: " . str_replace('-', '', implode('', $tinhthanh[0])) . "</br>";
        }
        foreach ($nganhnghetitle as $i => $item) {
            if ($i == 3) {
                echo "Nganh nghe title: " . $item->plaintext . "</br>";
            }
        }
        foreach ($title as $item) {
            echo "Title: " . $item->plaintext . "</br>";
        }


    }

}
