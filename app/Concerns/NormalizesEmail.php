<?php

namespace App\Concerns;

use Illuminate\Support\Str;

trait NormalizesEmail
{
    /**
     * Normalize the email address before validation.
     */
    protected function normalizeEmail(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge([
                'email' => Str::lower(trim($email)),
            ]);
        }
    }
}
