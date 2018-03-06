<?php

/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 19/07/2017
 * Time: 16:39
 */
namespace App\Colombo\Download\Exceptions;

use Throwable;

class FilterLinkException extends \Exception
{
    function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $message = 'only download '.$message . ' link';
        parent::__construct($message, $code, $previous);
    }
}