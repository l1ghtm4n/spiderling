<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 31/07/2017
 * Time: 15:27
 */

namespace App\Colombo\Uploader;


use App\Models\DownloadLink;

class NameFileResolver
{
    private $downloadLink;
    function __construct($downloadLink)
    {
        $this->downloadLink = $downloadLink;
    }


    /**
     * @param $input DownloadLink
     * @return mixed|string
     */
    public function handleTitle($input){
        $minName = config('crawler.document.handle_title.min_name');
        $min_name_link_text = config('crawler.document.handle_title.min_name_link_text');
        $min_name_link_text_page_title = config('crawler.document.handle_title.min_name_link_text_page_title');
        $country = '';
        if (!empty($input->site->country)){
            $country = $input->site->country;
        }
        if (is_object($input)){
            if (strlen($input->name) >= $minName){
                $title = $input->name;
                return $title;
            }else{
                $link_text = $this->filterString($input->link_text, $country);
                if (empty($input->name)){
                    $title = $link_text;
                }else{
                    $title = $this->mergeString($input->name, $link_text);
                }
                if (strlen($title) >= $min_name_link_text){
                    return $title;
                }else{
                    $page_title = $this->filterString($input->page_title, $country);
                    $title_link_page = $this->mergeString($link_text, $page_title);
                    $title = $title_link_page . ' ' . $input->name;
                    if (strlen($title) > $min_name_link_text_page_title){
                        return $title;
                    }else{
                        return '';
                    }
                }
            }
        }else{
            return '';
        }
    }
    public function mergeString($firstString, $secondString){
        $similarPercentMin = 40;
        $similarPercent = $this->similarity($firstString, $secondString);
        if ($similarPercent > $similarPercentMin){
            $string = $secondString;
        }else{
            //Ghép chuỗi
            $string = $secondString . ' ' . $firstString;
        }
        return $string;
    }

    public function filterString($string, $country = ''){
        if (empty($country)){
            $country = config('app.locale');
        }
        if (empty(array_get(config('country.list'), $country))){
            return $string;
        }
        $listSpecialKeywords = config('crawler.document.special_keywords.' . $country);
        foreach ($listSpecialKeywords as $specialString){
            $specialString = strtolower($specialString);
            $string = str_replace($specialString, '', $string);
            $string = str_replace(ucfirst($specialString), '', $string);
            $string = str_replace(ucwords($specialString), '', $string);
            $string = str_replace(strtoupper($specialString), '', $string);
        }
        $listSpecialStrings = config('crawler.document.special_strings');
        foreach ($listSpecialStrings as $specialString){
            $string = str_replace($specialString, '', $string);
        }
        return $string;
    }

    public function similarity($str1, $str2) {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        if ($len1 == 0 | $len2 == 0){
            return 0;
        }
        if ($len1 == 0 && $len2 == 0){
            return 100;
        }
        $percent = 0;
        similar_text($str1, $str2, $percent);
        return intval($percent * 100);
    }
}