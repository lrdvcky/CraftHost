<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['error' => 'Доступ запрещен. Требуются права администратора.'], 403);
        }

        return $next($request);
    }
}