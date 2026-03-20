<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidOtpException extends HttpException
{
    public function __construct(string $message = 'Invalid or expired OTP code')
    {
        parent::__construct(422, $message);
    }
}
