<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {

            $request->headers->set('authorization',  $request->header('token'));
            if(Auth::guard('api')->check()) {
         
                return $next($request);
            } else {
                return apiResponse(null, 'You are not authenticated to this service');
            }
        } catch (Exception $exception) {
            return apiResponse(10,$exception->getMessage());
        }

    }
}
