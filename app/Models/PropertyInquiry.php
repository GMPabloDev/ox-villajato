<?php

namespace App\Models;

use Database\Factories\PropertyInquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $property_id
 * @property string $property_title
 * @property int $publisher_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Property|null $property
 */
#[Fillable(['property_title', 'publisher_id', 'name', 'email', 'phone', 'message'])]
class PropertyInquiry extends Model
{
    /** @use HasFactory<PropertyInquiryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
