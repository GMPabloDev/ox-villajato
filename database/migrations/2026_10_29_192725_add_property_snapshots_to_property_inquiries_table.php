<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('property_inquiries', function (Blueprint $table): void {
            $table->string('property_title')->nullable();
            $table->unsignedBigInteger('publisher_id')->nullable();
        });

        DB::table('property_inquiries')->update([
            'property_title' => DB::raw('(SELECT title FROM properties WHERE properties.id = property_inquiries.property_id)'),
            'publisher_id' => DB::raw('(SELECT user_id FROM properties WHERE properties.id = property_inquiries.property_id)'),
        ]);

        Schema::table('property_inquiries', function (Blueprint $table): void {
            $table->string('property_title')->nullable(false)->change();
            $table->unsignedBigInteger('publisher_id')->nullable(false)->change();
        });

        Schema::table('property_inquiries', function (Blueprint $table): void {
            $table->dropForeign(['property_id']);
            $table->index('publisher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasInquiriesWithoutProperties = DB::table('property_inquiries')
            ->whereNotExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('properties')
                    ->whereColumn('properties.id', 'property_inquiries.property_id');
            })
            ->exists();

        if ($hasInquiriesWithoutProperties) {
            throw new LogicException('Cannot restore the property inquiry foreign key while deleted properties still have inquiries.');
        }

        Schema::table('property_inquiries', function (Blueprint $table): void {
            $table->dropIndex(['publisher_id']);
            $table->dropColumn(['property_title', 'publisher_id']);
        });

        Schema::table('property_inquiries', function (Blueprint $table): void {
            $table->foreign('property_id')
                ->references('id')
                ->on('properties')
                ->cascadeOnDelete();
        });
    }
};
