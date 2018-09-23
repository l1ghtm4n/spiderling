<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CurlController extends Controller
{

    public function curl($link)
    {
        // Tạo mới một CURL
        $ch = curl_init($link);

        // Cấu hình cho CURL
        curl_setopt($ch, CURLOPT_URL, "https://freetuts.net/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Thực thi CURL
        $result =  curl_exec($ch);

        return $result;
    }

}
