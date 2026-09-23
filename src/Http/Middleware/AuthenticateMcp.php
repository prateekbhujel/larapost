<?php

namespace SocialSync\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMcp
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('larapost.mcp.token', '');

        if ($expected === '') {
            abort(503, 'LaraPost MCP is enabled but no server token is configured.');
        }

        $authorization = (string) $request->header('Authorization', '');
        $provided = str_starts_with($authorization, 'Bearer ')
            ? substr($authorization, 7)
            : '';

        if ($provided === '' || !hash_equals($expected, $provided)) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Bearer realm="LaraPost MCP"',
            ]);
        }

        return $next($request);
    }
}
