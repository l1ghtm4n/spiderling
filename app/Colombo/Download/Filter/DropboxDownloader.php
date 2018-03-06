<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 26/07/2017
 * Time: 09:27
 */

namespace App\Colombo\Download\Filter;


use App\Colombo\Download\Contract\DownloaderAbstract;
use App\LinkProcessor\DropBox;

class DropboxDownloader extends DownloaderAbstract
{

    function run($link)
    {
        $this->link = $link;
        // TODO: Implement run() method.
        $link = $this->getLinkDownload($link);
        $result = $this->fileToTmp($link);
        return $result;

    }

    public function getLinkDownload($link){
        $regex = '/dl=\d+/';
        $dropbox_link_checker = new DropBox();
        if (!$dropbox_link_checker->check($link)){
            throw new \Exception('Link must link dropbox : ' . $link);
        }
        if (preg_match($regex, $link)){
            $download_link = preg_replace($regex, 'dl=1', $link);
        }else{
            $download_link = $link . '?dl=1';
        }
        return $download_link;
    }
}