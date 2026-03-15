<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UserNotVerifiedException extends HttpException
{
    public function __construct()
    {
        parent::__construct(403, 'Email is not verified');
    }
}
