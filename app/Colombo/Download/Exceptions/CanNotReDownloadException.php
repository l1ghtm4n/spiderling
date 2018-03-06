<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 20/07/2017
 * Time: 09:04
 */

namespace App\Colombo\Download\Exceptions;


use Throwable;

class CanNotReDownloadException extends \Exception
{
    function __construct($id = "", $code = 0, Throwable $previous = null)
    {
        $message = 'Can not redownload from document link with id '.$id;
        parent::__construct($message, $code, $previous);
    }
}