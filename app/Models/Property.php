<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PropertyOperation;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property PropertyOperation $operation
 * @property PropertyStatus $status
 * @property PropertyType $type
 * @property string $title
 * @property string $description
 * @property string $price
 * @property Currency $currency
 * @property string $city
 * @property string $zone
 * @property int $bedrooms
 * @property int $bathrooms
 * @property string $area
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, PropertyPhoto> $photos
 */
#[Fillable(['operation', 'status', 'type', 'title', 'description', 'price', 'currency', 'city', 'zone', 'bedrooms', 'bathrooms', 'area'])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'operation' => PropertyOperation::class,
            'status' => PropertyStatus::class,
            'type' => PropertyType::class,
            'price' => 'decimal:2',
            'currency' => Currency::class,
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'area' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<PropertyPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(PropertyPhoto::class)->orderBy('position');
    }

    public function isAvailable(): bool
    {
        return $this->status === PropertyStatus::Available;
    }

    #[Scope]
    protected function available(Builder $query): Builder
    {
        return $query->where('status', PropertyStatus::Available);
    }
}
