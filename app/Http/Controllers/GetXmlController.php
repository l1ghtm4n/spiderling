<?php

namespace App\Http\Controllers;

class GetXmlController extends Controller
{
    public function getViaLink($start = 0, $end = 10)
    {
        $context = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
        //for ($i = $start; $i <= $end; $i++) {
            //Load từ link
            //$url = "https://www.timviecnhanh.com/sitemap/sitemap-employer-" . $i . ".xml";
            $url = "http://www.hotel84.com/sitemap.xml";
            $xml = file_get_contents($url, false, $context);
            $xml = simplexml_load_string($xml);

            //Hàm dùng chung
            $x = 1;
            foreach ($xml->children() as $item) {
                //Get từ link
                //dump(trim(str_replace('\n', '', (string)$item->loc)));

                \DB::table('hotel84_links')->insert([
                    'link' => trim(str_replace('\n', '', (string)$item->loc)),
                ]);
                echo "Insert success " . $x . "\n";
                $x++;
            }

            //echo "-----------------Lay xong link trang xml " . $i . "\n";
            echo "-----------------Lay xong link trang xml\n";
        //}
    }

    public function getViaFile($start = 1, $end = 1)
    {
        //Load từ file
        //for ($i = $start; $i <= $end; $i++) {
            $xml = simplexml_load_file('public/hotel84/sitemap.xml');
            $x = 1;
            foreach ($localConfig->children() as $item) {
                //Get từ link
                dump(trim(str_replace('\n', '', (string)$item->loc)));

                //\DB::table('timviecnhanh_links')->insert([
                //    'link' => trim(str_replace('\n', '', (string)$item->loc)),
                //]);
                //echo "Insert success " . $x . "\n";
                //$x++;
            }
            die;
            //echo "-----------------Lay xong link trang xml " . $i . "\n";
            echo "-----------------Lay xong link trang xml \n";
        //}
    }
}
