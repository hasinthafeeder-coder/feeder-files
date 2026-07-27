<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApplicationApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->header('Authorization');

        if (!$authorization) {
            return response()->json([
                'message' => 'Authorization header is missing.',
            ], 401);
        }

        if (!str_starts_with($authorization, 'Bearer ')) {
            return response()->json([
                'message' => 'Invalid authorization format.',
            ], 401);
        }

        $apiKey = substr($authorization, 7);

        if ($apiKey !== config('file-server.api_key')) {
            return response()->json([
                'message' => 'Invalid API key.',
            ], 401);
        }

        return $next($request);
    }
}
