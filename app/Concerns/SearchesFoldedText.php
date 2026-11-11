<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait SearchesFoldedText
{
    /**
     * Characters folded to their plain form when searching and sorting text.
     *
     * @var array<string, string>
     */
    private const FOLDED_CHARACTERS = [
        'Á' => 'a', 'á' => 'a',
        'É' => 'e', 'é' => 'e',
        'Í' => 'i', 'í' => 'i',
        'Ó' => 'o', 'ó' => 'o',
        'Ú' => 'u', 'ú' => 'u',
        'Ü' => 'u', 'ü' => 'u',
        'Ñ' => 'n', 'ñ' => 'n',
    ];

    /**
     * Add a condition matching the term in any of the columns, ignoring case
     * and accents.
     *
     * @param  Builder<*>  $query
     * @param  list<literal-string>  $columns
     */
    protected static function addFoldedSearch(Builder $query, array $columns, string $term): void
    {
        $query->where(function (Builder $query) use ($columns, $term): void {
            self::addFoldedMatches($query, $columns, $term);
        });
    }

    /**
     * Add an OR condition for each column expression matching the term, ignoring
     * case and accents. Qualified columns such as "districts.name" are accepted.
     *
     * @param  Builder<*>  $query
     * @param  list<literal-string>  $expressions
     */
    protected static function addFoldedMatches(Builder $query, array $expressions, string $term): void
    {
        $driver = $query->getModel()->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            self::registerSqliteFold($query);
        }

        $search = $driver === 'mysql' ? trim($term) : Str::lower(Str::ascii(trim($term)));
        $pattern = '%'.self::escapeLikePattern($search).'%';

        foreach ($expressions as $expression) {
            $folded = match ($driver) {
                'sqlite' => "panel_search_fold({$expression})",
                'mysql' => "{$expression} COLLATE utf8mb4_unicode_ci",
                default => self::foldedColumn($expression),
            };

            $query->orWhereRaw($folded." LIKE ? ESCAPE '!'", [$pattern]);
        }
    }

    /**
     * @param  Builder<*>  $query
     */
    protected static function registerSqliteFold(Builder $query): void
    {
        $query->getModel()->getConnection()->getPdo()->sqliteCreateFunction('panel_search_fold',
            static fn (?string $value): string => Str::lower(Str::ascii($value ?? '')),
            1,
        );
    }

    /**
     * Fold a column to lowercase without accents so LIKE and ORDER BY compare
     * the same way on SQLite and MySQL.
     *
     * @param  literal-string  $column
     * @return literal-string
     */
    protected static function foldedColumn(string $column): string
    {
        $expression = $column;

        foreach (self::FOLDED_CHARACTERS as $accented => $plain) {
            $expression = "REPLACE({$expression}, '{$accented}', '{$plain}')";
        }

        return "LOWER({$expression})";
    }

    /**
     * Escape the wildcards of a LIKE pattern so the term matches literally.
     */
    protected static function escapeLikePattern(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
