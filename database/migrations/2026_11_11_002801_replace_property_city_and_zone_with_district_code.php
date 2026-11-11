<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Environments whose existing properties may be discarded to adopt the
     * mandatory district location.
     *
     * @var list<string>
     */
    private const DISCARDABLE_ENVIRONMENTS = ['local', 'testing'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->discardPropertiesOutsideOfProduction();

        Schema::table('properties', function (Blueprint $table): void {
            $table->string('district_code', 6)->after('currency');
            $table->foreign('district_code')->references('code')->on('districts')->restrictOnDelete();
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn(['city', 'zone']);
        });
    }

    /**
     * Remove the existing properties when their environment allows it.
     *
     * The guard runs before any schema change so a database with real data is
     * left untouched until an approved migration plan exists.
     */
    private function discardPropertiesOutsideOfProduction(): void
    {
        if (! DB::table('properties')->exists()) {
            return;
        }

        if (! app()->environment(self::DISCARDABLE_ENVIRONMENTS)) {
            throw new RuntimeException(
                'La migración necesita eliminar las propiedades existentes para exigir un distrito y el entorno actual no es de desarrollo ni de pruebas. '
                .'Conserva los datos: define y aprueba un plan de migración de las propiedades existentes antes de volver a ejecutarla.'
            );
        }

        DB::table('properties')->delete();
    }

    /**
     * Reverse the migrations.
     *
     * The city and zone values removed in up() cannot be restored, so the
     * columns come back nullable to keep the rollback usable.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropForeign(['district_code']);
            $table->dropColumn('district_code');
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->string('city')->nullable();
            $table->string('zone')->nullable();
        });
    }
};
