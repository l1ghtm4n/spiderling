<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 21/07/2017
 * Time: 08:36
 */

namespace App\Colombo\Download\Exceptions;


use Throwable;

class DownloadedException extends \Exception
{
    function __construct($id = "", $code = 0, Throwable $previous = null)
    {
        $message = 'Can not redownload from document link with id '.$id;
        parent::__construct($message, $code, $previous);
    }
}