<?php

namespace App\Http\Controllers;

use DB;

class GetJsonController extends Controller
{
    public function index()
    {
        set_time_limit(10000);
        $company_clone = DB::table('company_clones');
        for ($i = 2616; $i <= 5000; $i++) {
            sleep(1);
            try {
                $json = file_get_contents('https://thongtindoanhnghiep.co/api/company?p=' . $i);
            } catch (\Exception $e) {
                echo "Loi tai trang " . $i . "!\n";
                echo "Thu lai...\n";
                sleep(2);
                $json = file_get_contents('https://thongtindoanhnghiep.co/api/company?p=' . $i);
            }
            $obj = json_decode($json);
            $data = $obj->LtsItems;
            foreach ($data as $item) {
                try {
                    $company_clone->insert([
                        'masothue'       => $item->MaSoThue,
                        'ngaycap'        => $item->NgayCap,
                        'title'          => $item->Title,
                        'isdelete'       => $item->IsDelete == 'true' ? 1 : 0,
                        'diachicongty'   => $item->DiaChiCongTy,
                        'tinhthanhtitle' => $item->TinhThanhTitle,
                        'nganhnghetitle' => $item->NganhNgheTitle,
                    ]);
                } catch (\Exception $e) {
                    echo "Ban ghi nay da co tai trang " . $i . "!\n";
                }
            }
            echo "Lay thanh cong trang " . $i . "!\n";
        }
    }

    public function getContentVivu()
    {
        set_time_limit(10000);
        for ($i = 2; $i <= 31; $i++) {
            sleep(1);
            try {
                $json = file_get_contents('https://www.ivivu.com/hotelslist?regionId=853&page=' . $i . '&pageSize=100');
            } catch (\Exception $e) {
                echo "Loi tai trang " . $i . "!\n";
                echo "Thu lai...\n";
                sleep(2);
                $json = file_get_contents('https://www.ivivu.com/hotelslist?regionId=853&page=' . $i . '&pageSize=100');
            }
            $obj = json_decode($json);
            $obj = $obj->List;
            foreach ($obj as $x => $item) {
                $data = [];
                $data['name'] = $item->HotelName;
                $data['address'] = $item->Address;
                $data['lat_lon'] = $item->Lat.'|'. $item->Lon;
                $data['rate'] = $item->Rating;
                $data['point'] = intval($item->Point);
                $data['price_min'] = trim(str_replace('VND', '', $item->MinPrice));
                $data['price_max'] = trim(str_replace('VND', '', $item->MaxPrice));
                $data['thumb'] = $item->Avatar;
                $data['description'] = implode('|', $item->Description);
                $data['ean_code'] = $item->EANCode;
                $data['web'] = 'https://www.ivivu.com/'.$item->HotelLink;
                \DB::table('ivivu_contents')->insert($data);
                echo "Insert thanh cong link " . $i . " tai record " . ($x + 1)."\n";
            }
        }
    }

}
