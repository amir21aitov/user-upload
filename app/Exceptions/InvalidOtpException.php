<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidOtpException extends HttpException
{
    public function __construct()
    {
        parent::__construct(422, 'Invalid or expired OTP code');
    }
}
