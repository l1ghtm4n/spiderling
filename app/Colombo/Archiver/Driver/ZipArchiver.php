<?php

namespace App\Colombo\Archiver\Driver;
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 07/08/2017
 * Time: 10:59
 */
class ZipArchiver extends ArchiverAbstract
{

    public function extract($archive, $destination){
        if (!class_exists('ZipArchive')){
            return [
                'success' => false,
                'message' => 'Error: Your PHP version does not support .zip archive functionality.'
            ];
        }
        $return = [
            'success' => false,
        ];
        $zip = new \ZipArchive();
        if ($zip->open($archive) == true){
            $numFiles = $zip->numFiles;
            if (!is_dir($destination)){
                mkdir($destination, 0777, true);
            }
            $result = $zip->extractTo($destination);
            $zip->close();
            if ($result){
                $return = [
                    'success' => true,
                    'dir' => $destination,
                    'numFiles' => $numFiles
                ];
            }else{
                $return = [
                    'success' => false,
                    'message' => 'Error: File not decompress successfully'
                ];
            }
        }else{
            $return['message'] = 'Error: Cannot read zip archive file';
        }
        return $return;
    }

}