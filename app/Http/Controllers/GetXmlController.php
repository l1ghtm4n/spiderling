<?php

namespace App\Http\Controllers;

class GetXmlController extends Controller
{
    public function index()
    {
        $x = 1;
        $xml = simplexml_load_file('public/thongdinhdoanhnghiep/sitemap_22.xml');
        foreach ($xml->children() as $item) {
            \DB::table('company_link')->insert([
                'link' => (string)$item->loc,
            ]);
            echo "Sitemap_22 Insert success " . $x . "\n";
            $x++;
        }
    }
}
