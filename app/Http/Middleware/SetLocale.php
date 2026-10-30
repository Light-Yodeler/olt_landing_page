<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $segment = $request->segment(1);

        if ($segment === 'id') {
            App::setLocale('id');
        } else {
            App::setLocale('en');
        }

        return $next($request);
    }
}
