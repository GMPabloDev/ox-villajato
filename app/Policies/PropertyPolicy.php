<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    /**
     * Grant administrators full access before the specific checks.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Determine if the user can update the property.
     */
    public function update(User $user, Property $property): bool
    {
        return $property->user_id === $user->id;
    }
}
