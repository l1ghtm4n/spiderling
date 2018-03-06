<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 07/08/2017
 * Time: 14:00
 */

namespace App\Colombo\Archiver\Driver;


abstract class ArchiverAbstract
{
    abstract function extract($archive, $destination);
}