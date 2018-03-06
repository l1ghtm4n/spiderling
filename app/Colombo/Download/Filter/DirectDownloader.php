<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 24/07/2017
 * Time: 11:44
 */

namespace App\Colombo\Download\Filter;


use App\Colombo\Download\Contract\DownloaderAbstract;
use App\Helpers\PhpUri;

class DirectDownloader extends DownloaderAbstract
{

    function run($link)
    {
        $this->link = PhpUri::urlEncode($link);
        $result = $this->fileToTmp($this->link);
        return $result;
    }
}