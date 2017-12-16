<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\ParameterBag;
use Closure;

class TrimRequestInput
{
    protected $except = [
    ];

    public function handle($request, Closure $next)
    {
        $this->clean($request->query);

        $request->isJson() ? $this->clean($request->json()) : $this->clean($request->request);

        return $next($request);
    }

    protected function clean(ParameterBag $parameterBag)
    {
        $parameterBag->replace($this->cleanArray(
            $parameterBag->all()
        ));
    }

    protected function cleanArray(array $parameter)
    {
        return collect($parameter)->map(function ($value, $key) {
            return $this->cleanValue($key, $value);
        })->all();
    }

    protected function cleanValue($key, $value)
    {
        return is_array($value) ? $this->cleanArray($value) : $this->transform($key, $value);
    }

    protected function transform($key, $value)
    {
        if (!in_array($key, $this->except, true) && is_string($value)) {
            ($value = trim($value)) === '' && $value = null;
        }

        return $value;
    }
}
