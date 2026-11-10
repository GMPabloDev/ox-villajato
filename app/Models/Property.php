<?php

namespace App\Models;

use App\Concerns\SearchesFoldedText;
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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
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
 * @property-read Collection<int, PropertyInquiry> $inquiries
 */
#[Fillable(['operation', 'status', 'type', 'title', 'description', 'price', 'currency', 'city', 'zone', 'bedrooms', 'bathrooms', 'area'])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    use SearchesFoldedText;

    /**
     * Columns searched by the panel search box.
     *
     * @var list<literal-string>
     */
    private const SEARCHABLE_COLUMNS = ['title', 'city', 'zone'];

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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<PropertyPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(PropertyPhoto::class)->orderBy('position');
    }

    /**
     * @return HasMany<PropertyInquiry, $this>
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(PropertyInquiry::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === PropertyStatus::Available;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function available(Builder $query): Builder
    {
        return $query->where('status', PropertyStatus::Available);
    }

    /**
     * Limit the query to properties that are not archived.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function notArchived(Builder $query): Builder
    {
        return $query->where('status', '!=', PropertyStatus::Archived);
    }

    /**
     * Limit the query to the properties the user is allowed to manage.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin()
            ? $query
            : $query->whereBelongsTo($user);
    }

    /**
     * Match the term in the title, city or zone, ignoring case and accents.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        self::addFoldedSearch($query, self::SEARCHABLE_COLUMNS, $term);

        return $query;
    }

    /**
     * Order the query by title from A to Z, ignoring case and accents.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function orderedByTitle(Builder $query): Builder
    {
        $sqlite = $query->getModel()->getConnection()->getDriverName() === 'sqlite';

        if ($sqlite) {
            self::registerSqliteFold($query);
        }

        return $query
            ->orderByRaw($sqlite ? 'panel_search_fold(title)' : self::foldedColumn('title'))
            ->orderBy('id');
    }
}
