<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        Middleware\CORSEncryptCookies::class,
        Middleware\CORSAddQueuedCookiesToResponse::class,
        Middleware\CORSStartSession::class,
        Middleware\TrimRequestInput::class,
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
    ];
}
