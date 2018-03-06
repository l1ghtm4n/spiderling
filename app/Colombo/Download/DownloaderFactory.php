<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 26/07/2017
 * Time: 09:28
 */

namespace App\Colombo\Download;


use App\Colombo\Download\Exceptions\WrongFilterException;
use App\Colombo\Download\Filter\DirectDownloader;
use App\Colombo\Download\Filter\DropboxDownloader;
use App\Colombo\Download\Filter\GoogleDriveDownloader;

class DownloaderFactory
{
    public static function buildDownloader($filter){
        switch ($filter){
            case 'downloadable':
                $downloadLink = new DirectDownloader();
                break;
            case 'google_drive' :
                $downloadLink = new GoogleDriveDownloader();
                break;
            case 'dropbox' :
                $downloadLink = new DropboxDownloader();
                break;
            default:
                throw new WrongFilterException($filter);
        }
        return $downloadLink;
}
}