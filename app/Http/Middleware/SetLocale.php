<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'id'; // default

        if (auth()->check()) {
            $locale = auth()->user()->locale ?? 'id';
        } elseif (session()->has('locale')) {
            $locale = session('locale');
        }

        if (in_array($locale, ['id', 'en', 'ja'])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
