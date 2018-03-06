<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 07/08/2017
 * Time: 10:50
 */

namespace App\Colombo\Archiver;

use App\Colombo\Archiver\Driver\RarArchiver;
use App\Colombo\Archiver\Driver\ZipArchiver;

class Archiver
{
    private $destination = '';
    private $archive = '';

    public function compress($archive, $destination){

    }

    public function decompress($archive, $destination){
        $ext = $this->getExtension($archive);
        $this->archive = $archive;
        switch ($ext){
            case 'zip':
                $archiver = new ZipArchiver();
                $result = $archiver->extract($archive, $destination);
                break;
            case 'rar' :
                $archiver = new RarArchiver();
                $result = $archiver->extract($archive, $destination);
                break;
            default:
                $result = [
                    'success' => false,
                    'message' => 'No support ext : ' . $ext
                ];
                break;
        }
        return $result;
    }

    private function getExtension($file_path){
        $ext = \File::extension($file_path);
        return $ext;
    }

    public function __destruct()
    {
        \File::delete($this->archive);
    }
}