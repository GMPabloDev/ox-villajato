<?php

namespace App\Models;

use App\Concerns\SearchesFoldedText;
use Database\Factories\PropertyInquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
 * @property-read User|null $publisher
 */
#[Fillable(['property_title', 'publisher_id', 'name', 'email', 'phone', 'message'])]
class PropertyInquiry extends Model
{
    /** @use HasFactory<PropertyInquiryFactory> */
    use HasFactory;

    use SearchesFoldedText;

    /**
     * Columns searched by the panel search box.
     *
     * @var list<literal-string>
     */
    private const SEARCHABLE_COLUMNS = ['name', 'email', 'property_title', 'message'];

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Limit the query to the inquiries the user is allowed to read.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin()
            ? $query
            : $query->where('publisher_id', $user->id);
    }

    /**
     * Match the term in the visitor name, email, property title or message,
     * ignoring case and accents.
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
}
