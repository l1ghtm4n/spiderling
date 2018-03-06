<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 24/07/2017
 * Time: 11:50
 */

namespace App\Colombo\Download\Filter;


use App\Colombo\Download\Contract\DownloaderAbstract;
use App\LinkProcessor\GoogleDrive;

class GoogleDriveDownloader extends DownloaderAbstract
{

    function run($link)
    {
        $this->link = $link;
        $link_download = $this->getLinkDownload($this->link);
        $result = $this->fileToTmp($link_download);
        return $result;
    }
    public function getLinkDownload($href){
//        $href = "https://docs.google.com/presentation/d/1py_0WFsIN5zUnxaqfT1fazgFQfCia6zCgoyYu5kP7Ls/edit#slide=id.p";
        $presentation_link = 'https://docs.google.com/presentation/d/__FILE_ID__/export/pdf';
        $spreadsheets_link = 'https://docs.google.com/spreadsheets/d/__FILE_ID__/export?format=pdf';
        $document_link = 'https://docs.google.com/document/d/__FILE_ID__/export?format=docx';
        $file_link = "https://drive.google.com/uc?export=download&id=__FILE_ID__";

        $google_link_checker = new GoogleDrive();
        if (!$google_link_checker->check($href)){
            throw new \Exception('link must be a link drive');
        }
        $matches = [];
        if(preg_match('/[\w_-]{25,}/', $href, $matches)){
            $file_id = $matches[0];
        }else{
            throw new \Exception('Can not find id from ' . $href);
        }
        if (preg_match("/\/file\//", $href)){
            $link = str_replace_first('__FILE_ID__', $file_id, $file_link);
        }elseif (preg_match("/\/presentation\//", $href)){
            $link = str_replace_first('__FILE_ID__', $file_id, $presentation_link);
        }elseif (preg_match("/\/spreadsheets\//", $href)){
            $link = str_replace_first('__FILE_ID__', $file_id, $spreadsheets_link);
        }elseif (preg_match("/\/document\//", $href)){
            $link = str_replace_first('__FILE_ID__', $file_id, $document_link);
        }else{
            $link = str_replace_first('__FILE_ID__', $file_id, $file_link);
        }
        return $link;

    }
}