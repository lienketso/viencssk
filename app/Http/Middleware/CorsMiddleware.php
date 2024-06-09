<?php

namespace App\Http\Middleware;
use Closure;
class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        $allowedOrigins = [
            'https://h5.zdn.vn',
            'zbrowser://h5.zdn.vn'
        ];

        $origin = $request->headers->get('Origin');

        if ($origin && in_array($origin, $allowedOrigins)) {
            $headers = [
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
            ];

            return $next($request)->withHeaders($headers);
        }

        return $next($request);
    }
}