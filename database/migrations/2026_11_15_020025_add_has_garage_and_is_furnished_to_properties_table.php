<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Environments whose existing properties may be discarded to require an
     * explicit answer about the garage and the furnishing.
     *
     * @var list<string>
     */
    private const DISCARDABLE_ENVIRONMENTS = ['local', 'testing'];

    /** Prevent a schema transaction from restoring rows after files have been deleted. */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     *
     * If the schema change fails after properties are deleted, their photo paths
     * are lost and the files remain orphaned. Before running this migration,
     * retain the paths from property_photos and the configured photos disk. If
     * that failure occurs, use that record to remove the orphaned files manually;
     * rerunning the migration cannot recover the paths.
     */
    public function up(): void
    {
        $photoPaths = $this->discardPropertiesWithoutTheNewAnswers();

        Schema::table('properties', function (Blueprint $table): void {
            $table->boolean('has_garage')->after('area');
            $table->boolean('is_furnished')->after('has_garage');
        });

        $this->deleteDiscardedPhotos($photoPaths);
    }

    /**
     * Remove the existing properties when their environment allows it.
     *
     * Every property stored before this migration lacks both answers because
     * the columns are added here. The guard runs before any schema change so a
     * database with real data is left untouched until an approved migration
     * plan exists. The inquiries of the removed properties keep the stored
     * title and publisher, so the inquiry history survives.
     *
     * @return list<string>
     */
    private function discardPropertiesWithoutTheNewAnswers(): array
    {
        if (! DB::table('properties')->exists()) {
            return [];
        }

        if (! app()->environment(self::DISCARDABLE_ENVIRONMENTS)) {
            throw new RuntimeException(
                'La migración necesita eliminar las propiedades existentes porque no indican si tienen garaje ni si están amuebladas, y el entorno actual no es de desarrollo ni de pruebas. '
                .'Conserva los datos: define y aprueba un plan de migración de las propiedades existentes antes de volver a ejecutarla.'
            );
        }

        $photoPaths = [];
        foreach (DB::table('property_photos')->orderBy('id')->pluck('path') as $path) {
            if (! is_string($path)) {
                throw new RuntimeException('La ruta de una foto no es válida.');
            }
            $photoPaths[] = $path;
        }

        DB::table('properties')->delete();

        return $photoPaths;
    }

    /**
     * Failed deletions leave orphan files. The migration completes and cannot
     * retry them; their paths are logged for manual cleanup.
     *
     * @param  list<string>  $photoPaths
     */
    private function deleteDiscardedPhotos(array $photoPaths): void
    {
        foreach ($photoPaths as $path) {
            try {
                if (! Storage::disk((string) config('properties.photos_disk'))->delete($path)) {
                    throw new RuntimeException('No se pudo eliminar la foto: '.$path);
                }
            } catch (Throwable $exception) {
                Log::warning('No se pudo eliminar una foto descartada; requiere limpieza manual.', [
                    'path' => $path,
                    'exception' => $exception,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * The properties deleted in up() cannot be restored, so the rollback only
     * drops the new columns.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn(['has_garage', 'is_furnished']);
        });
    }
};
