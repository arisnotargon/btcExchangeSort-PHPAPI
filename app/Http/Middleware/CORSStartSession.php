<?php

namespace App\Http\Middleware;

use Illuminate\Session\Middleware\StartSession;

use Illuminate\Support\Arr;
use Illuminate\Session\SessionInterface;
use Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

use Auth;

class CORSStartSession extends StartSession
{
    protected function addCookieToResponse(Response $response, SessionInterface $session)
    {
        if (empty(Request::header('Origin')) && Auth::check()) {
            if ($this->usingCookieSessions()) {
                $this->manager->driver()->save();
            }

            if ($this->sessionIsPersistent($config = $this->manager->getSessionConfig())) {
                $response->headers->setCookie(new Cookie(
                    $session->getName(), $session->getId(), $this->getCookieExpirationDate(),
                    $config['path'], $config['domain'], Arr::get($config, 'secure', false), false
                ));
            }
        }
    }
}
