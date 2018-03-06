<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 07/08/2017
 * Time: 15:09
 */

namespace App\Colombo\Archiver\Driver;


class RarArchiver extends ArchiverAbstract
{

    /**
     * @param $archive
     * @param $destination
     * @return array
     */
    function extract($archive, $destination)
    {
        if (!class_exists('RarArchive')){
            return [
                'success' => false,
                'message' => 'Error: Your PHP version does not support .rar archive functionality.'
                .'<a class="info" href="http://php.net/manual/en/rar.installation.php" target="_blank">How to install RarArchive</a>'
            ];
        }

        if ($rar = \RarArchive::open($archive)){
            if (!is_dir($destination)){
                mkdir($destination, 0777, true);
            }
            $entries = $rar->getEntries();
            foreach ($entries as $entry){
                $entry->extract($destination);
            }
            $rar->close();
            return [
              'success' => true,
              'dir' => $destination
            ];
        }else{
            return [
                'success' => false,
                'message' => 'Error: Cannot read .rar archive'
            ];
        }
    }
}