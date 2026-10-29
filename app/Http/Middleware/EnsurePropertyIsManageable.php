<?php

namespace App\Http\Middleware;

use App\Models\Property;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePropertyIsManageable
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $property = $request->route('property');
        $user = $request->user();

        if (! $property instanceof Property || ! $user instanceof User || $user->cannot('update', $property)) {
            abort(404);
        }

        return $next($request);
    }
}
