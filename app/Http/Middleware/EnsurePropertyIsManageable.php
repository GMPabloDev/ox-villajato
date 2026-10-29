<?php

namespace App\Http\Middleware;

use App\Enums\PropertyStatus;
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
    public function handle(Request $request, Closure $next, ?string $statusRequirement = null): Response
    {
        $property = $request->route('property');
        $user = $request->user();

        if (! $property instanceof Property || ! $user instanceof User) {
            abort(404);
        }

        if (
            $user->cannot('update', $property)
            || ! $this->isStatusAllowed($property, $statusRequirement)
        ) {
            abort(404);
        }

        return $next($request);
    }

    private function isStatusAllowed(Property $property, ?string $statusRequirement): bool
    {
        return match ($statusRequirement) {
            'archived' => $property->status === PropertyStatus::Archived,
            'closed' => in_array($property->status, [PropertyStatus::Sold, PropertyStatus::Rented], true),
            null => $property->status !== PropertyStatus::Archived,
            default => false,
        };
    }
}
