<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies;

use Closure, Session;

class CORSEncryptCookies extends EncryptCookies
{
    public function handle($request, Closure $next)
    {
        if ($request->isMethod('OPTIONS')) {
            $next($request)->sendHeaders();
            exit();
        }

        if (!empty($authorization = $request->header('Authorization'))) {
            $request->cookies->set(Session::getName(), explode(' ', $authorization)[1]);
        }

        return parent::handle($request, $next);
    }

    public function isDisabled($name)
    {
        return $name !== Session::getName();
    }
}
