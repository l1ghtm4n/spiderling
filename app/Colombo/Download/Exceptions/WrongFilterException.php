<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 21/07/2017
 * Time: 08:35
 */

namespace App\Colombo\Download\Exceptions;


use Throwable;

class WrongFilterException extends \Exception
{
    function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $message = 'Not support download from link filter: ' . $message;
        parent::__construct($message, $code, $previous);
    }
}