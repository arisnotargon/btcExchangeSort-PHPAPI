<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Auth;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function authorizeAtCustomer()
    {
        return $this->authorize('customer');
    }

    protected function authorizeAtRole($role)
    {
        return $this->authorize('role', is_array($role) ? [$role] : [[$role]]);
    }

    protected function createGateUnauthorizedException($ability, $arguments, $message = 'This action is unauthorized.', $previousException = null)
    {
        return Auth::check() ? new HttpException(403) : new HttpException(401);
    }
}
