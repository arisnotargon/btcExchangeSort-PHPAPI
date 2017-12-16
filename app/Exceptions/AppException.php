<?php
namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Exception;

class AppException extends HttpException
{
    public function __construct($message, $code = 500, array $headers = [], Exception $previous = null)
    {
        parent::__construct($code, $message, $previous, $headers, $code);
    }
}
?>
