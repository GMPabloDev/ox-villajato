<?php

namespace App\Http\Controllers;

use App\Enums\PropertyOperation;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Http\Requests\PanelPropertyIndexRequest;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

class PropertyController extends Controller
{
    private const TEMPORARY_POSITION_OFFSET = 100;

    public function index(Request $request): Response|RedirectResponse
    {
        $validator = Validator::make(
            $request->query(),
            [
                'operation' => ['nullable', Rule::enum(PropertyOperation::class)],
                'type' => ['nullable', Rule::enum(PropertyType::class)],
                'zone' => ['nullable', 'string', 'max:255'],
            ],
            [
                'operation.enum' => 'El valor seleccionado para operación no es válido.',
                'type.enum' => 'El valor seleccionado para tipo no es válido.',
                'string' => 'El campo :attribute debe ser texto.',
                'max' => 'El campo :attribute no debe superar :max caracteres.',
            ],
            [
                'operation' => 'operación',
                'type' => 'tipo',
                'zone' => 'zona',
            ],
        );

        if ($validator->fails()) {
            return redirect()
                ->route('home')
                ->withErrors($validator);
        }

        /** @var array{operation?: string|null, type?: string|null, zone?: string|null} $filters */
        $filters = $validator->validated();

        $properties = Property::query()
            ->available()
            ->with('photos')
            ->when(
                $filters['operation'] ?? null,
                fn (Builder $query, string $operation): Builder => $query->where('operation', $operation),
            )
            ->when(
                $filters['type'] ?? null,
                fn (Builder $query, string $type): Builder => $query->where('type', $type),
            )
            ->when(
                $filters['zone'] ?? null,
                fn (Builder $query, string $zone): Builder => $query->where('zone', $zone),
            )
            ->latest('id')
            ->get();

        $zones = $this->sortLocations(
            Property::query()
                ->available()
                ->distinct()
                ->pluck('zone'),
        );

        return Inertia::render('properties/index', [
            'properties' => $properties
                ->map(fn (Property $property): array => (new PropertyResource($property))->resolve())
                ->all(),
            'zones' => $zones,
            'filters' => [
                'operation' => $filters['operation'] ?? null,
                'type' => $filters['type'] ?? null,
                'zone' => $filters['zone'] ?? null,
            ],
        ]);
    }

    public function panelIndex(PanelPropertyIndexRequest $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $disk = (string) config('properties.photos_disk');
        $filters = $request->filters();

        $accessibleQuery = Property::query()->notArchived()->visibleTo($user);

        $properties = $this->applyPanelSort(
            $this->applyPanelFilters($accessibleQuery->clone(), $filters, includeStatus: true)->with('photos'),
            $filters['sort'],
        )
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Property $property): array => [
                ...(new PropertyResource($property))->resolve(),
                'photos' => $property->photos
                    ->map(fn (PropertyPhoto $photo): array => [
                        'id' => $photo->id,
                        'url' => Storage::disk($disk)->url($photo->path),
                        'position' => $photo->position,
                    ])
                    ->values()
                    ->all(),
                'status' => $property->status->value,
                'status_label' => $property->status->label(),
                'status_action' => $property->status->action(),
                'status_action_label' => $property->status->actionLabel($property->operation),
            ]);

        if ($properties->currentPage() > $properties->lastPage()) {
            return redirect()->route('panel.properties.index', [
                ...$request->query(),
                'page' => $properties->lastPage(),
            ]);
        }

        $statusCounts = $this->applyPanelFilters($accessibleQuery->clone(), $filters, includeStatus: false)
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = [
            'all' => 0,
            'available' => (int) ($statusCounts[PropertyStatus::Available->value] ?? 0),
            'sold' => (int) ($statusCounts[PropertyStatus::Sold->value] ?? 0),
            'rented' => (int) ($statusCounts[PropertyStatus::Rented->value] ?? 0),
        ];
        $counts['all'] = $counts['available'] + $counts['sold'] + $counts['rented'];

        $locations = $accessibleQuery->clone()
            ->toBase()
            ->select(['city', 'zone'])
            ->distinct()
            ->get();

        $zonesOf = fn (Collection $rows): array => $this->sortLocations(
            $rows->pluck('zone')->map(fn (mixed $zone): string => (string) $zone)->unique(),
        );

        $zonesByCity = $locations
            ->groupBy(fn (object $location): string => (string) $location->city)
            ->map(fn (Collection $cityLocations): array => $zonesOf($cityLocations))
            ->all();

        $allZones = $zonesOf($locations);
        $priceRangeError = $request->priceRangeError();

        return Inertia::render('panel/properties/index', [
            'properties' => $properties,
            'filters' => $filters,
            'counts' => $counts,
            'cities' => $this->sortLocations(
                $locations->pluck('city')->map(fn (mixed $city): string => (string) $city)->unique(),
            ),
            'zones' => $filters['city'] === null
                ? $allZones
                : ($zonesByCity[$filters['city']] ?? []),
            'zonesByCity' => $zonesByCity,
            'filterErrors' => $priceRangeError === null ? [] : ['price' => $priceRangeError],
        ]);
    }

    /**
     * Apply the panel filters to the query.
     *
     * @param  Builder<Property>  $query
     * @param  array{
     *     search: string|null,
     *     status: string|null,
     *     operation: string|null,
     *     type: string|null,
     *     city: string|null,
     *     zone: string|null,
     *     currency: string|null,
     *     min_price: string|null,
     *     max_price: string|null,
     *     bedrooms: int|null,
     *     sort: string
     * }  $filters
     * @return Builder<Property>
     */
    private function applyPanelFilters(Builder $query, array $filters, bool $includeStatus): Builder
    {
        if ($filters['search'] !== null) {
            $query->search($filters['search']);
        }

        if ($includeStatus && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        if ($filters['operation'] !== null) {
            $query->where('operation', $filters['operation']);
        }

        if ($filters['type'] !== null) {
            $query->where('type', $filters['type']);
        }

        if ($filters['city'] !== null) {
            $query->where('city', $filters['city']);
        }

        if ($filters['zone'] !== null) {
            $query->where('zone', $filters['zone']);
        }

        if ($filters['currency'] !== null) {
            $query->where('currency', $filters['currency']);
        }

        if ($filters['min_price'] !== null) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if ($filters['max_price'] !== null) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if ($filters['bedrooms'] !== null) {
            $query->where('bedrooms', '>=', $filters['bedrooms']);
        }

        return $query;
    }

    /**
     * Apply the panel ordering to the query.
     *
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    private function applyPanelSort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'title' => $query->orderedByTitle(),
            'price-asc' => $query->orderBy('price')->orderBy('id'),
            'price-desc' => $query->orderByDesc('price')->orderBy('id'),
            default => $query->latest('id'),
        };
    }

    /**
     * Sort location options ignoring case and accents.
     *
     * @param  Collection<int, string>  $locations
     * @return list<string>
     */
    private function sortLocations(Collection $locations): array
    {
        return array_values(
            $locations
                ->sortBy(fn (string $location): string => Str::lower(Str::ascii($location)))
                ->all(),
        );
    }

    public function archivedIndex(Request $request): Response
    {
        $user = $request->user();

        $properties = Property::query()
            ->where('status', PropertyStatus::Archived)
            ->when(! $user->isAdmin(), fn (Builder $query): Builder => $query->whereBelongsTo($user))
            ->with('photos')
            ->latest('id')
            ->get();

        return Inertia::render('panel/properties/archived', [
            'properties' => $properties
                ->map(fn (Property $property): array => (new PropertyResource($property))->resolve())
                ->all(),
            'canDelete' => $user->isAdmin(),
        ]);
    }

    public function destroy(Request $request, Property $property): RedirectResponse
    {
        $request->validate(
            [
                'title_confirmation' => ['required', 'string', Rule::in([$property->title])],
            ],
            [
                'title_confirmation.required' => 'Escribe el título de la propiedad para confirmar.',
                'title_confirmation.in' => 'El título no coincide con la propiedad.',
            ],
        );

        $disk = (string) config('properties.photos_disk');
        $photoPaths = array_values(array_unique($property->photos()->pluck('path')->all()));
        $backupDirectory = 'property-deletion-backups/'.Str::uuid();
        $backups = [];

        try {
            $photoDisk = Storage::disk($disk);
            $backupDisk = Storage::disk('local');

            $this->backupPropertyPhotos($photoDisk, $backupDisk, $photoPaths, $backupDirectory, $backups);
        } catch (Throwable $exception) {
            if (isset($backupDisk)) {
                $this->discardPhotoBackups($backupDisk, $backupDirectory, $backups, $property->id);
            }

            Log::error('Unable to prepare archived property photos for deletion.', [
                'property_id' => $property->id,
                'exception' => $exception,
            ]);

            return $this->redirectWithPropertyDeletionError(
                'No se pudieron preparar las fotos para su eliminación. Inténtalo de nuevo.',
            );
        }

        $failureMessage = 'No se pudieron eliminar las fotos de la propiedad. Inténtalo de nuevo.';

        try {
            foreach ($photoPaths as $photoPath) {
                if (! $photoDisk->delete($photoPath)) {
                    throw new RuntimeException('Unable to delete property photo ['.$photoPath.'].');
                }
            }

            $failureMessage = 'No se pudo eliminar la propiedad. Inténtalo de nuevo.';

            DB::transaction(function () use ($property): void {
                if (! $property->delete()) {
                    throw new RuntimeException('Unable to delete property ['.$property->id.'].');
                }
            });
        } catch (Throwable $exception) {
            $restoreFailures = $this->restorePropertyPhotos($photoDisk, $backupDisk, $backups);

            if ($restoreFailures === []) {
                $this->discardPhotoBackups($backupDisk, $backupDirectory, $backups, $property->id);
            } else {
                Log::critical('Archived property photo restoration was incomplete.', [
                    'property_id' => $property->id,
                    'photo_paths' => $restoreFailures,
                    'backup_directory' => $backupDirectory,
                    'exception' => $exception,
                ]);
            }

            Log::error('Unable to delete archived property and its photos.', [
                'property_id' => $property->id,
                'exception' => $exception,
                'restore_failed_paths' => $restoreFailures,
            ]);

            return $this->redirectWithPropertyDeletionError(
                $restoreFailures === []
                    ? $failureMessage
                    : 'No se pudieron eliminar ni restaurar todas las fotos. Contacta con soporte.',
            );
        }

        $this->discardPhotoBackups($backupDisk, $backupDirectory, $backups, $property->id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Propiedad eliminada.',
        ]);

        return to_route('panel.properties.archived');
    }

    /**
     * @param  list<string>  $photoPaths
     * @param  array<string, array{path: string, visibility: string}>  $backups
     */
    private function backupPropertyPhotos(
        FilesystemAdapter $photoDisk,
        Filesystem $backupDisk,
        array $photoPaths,
        string $backupDirectory,
        array &$backups,
    ): void {
        foreach ($photoPaths as $index => $photoPath) {
            if (! $photoDisk->fileExists($photoPath)) {
                continue;
            }

            $backupPath = $backupDirectory.'/'.$index;
            $backups[$photoPath] = [
                'path' => $backupPath,
                'visibility' => Filesystem::VISIBILITY_PUBLIC,
            ];

            $photoStream = $photoDisk->readStream($photoPath);

            if (! is_resource($photoStream)) {
                throw new RuntimeException('Unable to read property photo ['.$photoPath.'] for backup.');
            }

            try {
                if (! $backupDisk->writeStream($backupPath, $photoStream)) {
                    throw new RuntimeException('Unable to back up property photo ['.$photoPath.'].');
                }
            } finally {
                fclose($photoStream);
            }
        }
    }

    /**
     * @param  array<string, array{path: string, visibility: string}>  $backups
     * @return list<string>
     */
    private function restorePropertyPhotos(FilesystemAdapter $photoDisk, Filesystem $backupDisk, array $backups): array
    {
        $failedPaths = [];

        foreach ($backups as $photoPath => $backup) {
            try {
                if ($photoDisk->fileExists($photoPath)) {
                    continue;
                }

                $backupStream = $backupDisk->readStream($backup['path']);

                if (! is_resource($backupStream)) {
                    $failedPaths[] = $photoPath;

                    continue;
                }

                try {
                    if (! $photoDisk->writeStream($photoPath, $backupStream, [
                        'visibility' => $backup['visibility'],
                    ])) {
                        $failedPaths[] = $photoPath;
                    }
                } finally {
                    fclose($backupStream);
                }
            } catch (Throwable $exception) {
                $failedPaths[] = $photoPath;

                Log::critical('Unable to restore property photo after failed deletion.', [
                    'photo_path' => $photoPath,
                    'backup_path' => $backup['path'],
                    'exception' => $exception,
                ]);
            }
        }

        return $failedPaths;
    }

    /**
     * @param  array<string, array{path: string, visibility: string}>  $backups
     */
    private function discardPhotoBackups(
        Filesystem $backupDisk,
        string $backupDirectory,
        array $backups,
        int $propertyId,
    ): void {
        if ($backups === []) {
            return;
        }

        try {
            if (! $backupDisk->deleteDirectory($backupDirectory)) {
                Log::warning('Unable to remove temporary property photo backups.', [
                    'property_id' => $propertyId,
                    'backup_directory' => $backupDirectory,
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('Unable to remove temporary property photo backups.', [
                'property_id' => $propertyId,
                'backup_directory' => $backupDirectory,
                'exception' => $exception,
            ]);
        }
    }

    private function redirectWithPropertyDeletionError(string $message): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'error',
            'message' => $message,
        ]);

        return to_route('panel.properties.archived');
    }

    public function close(Property $property): RedirectResponse
    {
        $status = $property->operation->closedStatus();

        $property->update(['status' => $status]);

        return $this->redirectToPanelWithStatus($status);
    }

    public function archive(Property $property): RedirectResponse
    {
        $property->update(['status' => PropertyStatus::Archived]);

        return $this->redirectToPanelWithStatus(PropertyStatus::Archived, 'Propiedad archivada.');
    }

    public function reopen(Property $property): RedirectResponse
    {
        $property->update(['status' => PropertyStatus::Available]);

        return $this->redirectToPanelWithStatus(PropertyStatus::Available);
    }

    public function republish(Property $property): RedirectResponse
    {
        $property->update(['status' => PropertyStatus::Available]);

        return $this->redirectToPanelWithStatus(PropertyStatus::Available, 'Propiedad republicada.');
    }

    private function redirectToPanelWithStatus(PropertyStatus $status, ?string $message = null): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $message ?? 'Propiedad marcada como '.Str::lower($status->label()).'.',
        ]);

        return to_route('panel.properties.index');
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var array<int, UploadedFile> $photos */
        $photos = $validated['photos'];

        unset($validated['photos']);

        $user = $request->user();

        $property = $user->properties()->create($validated);

        $directory = (string) config('properties.photos_directory');
        $disk = (string) config('properties.photos_disk');

        foreach ($photos as $index => $photo) {
            $property->photos()->create([
                'path' => $photo->storePublicly($directory, $disk),
                'position' => $index + 1,
            ]);
        }

        return redirect()->route('properties.show', $property);
    }

    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $validated = $request->validated();

        /** @var array<int, int> $photoOrder */
        $photoOrder = array_values(array_map('intval', (array) ($validated['photo_order'] ?? [])));

        /** @var array<int, UploadedFile> $newPhotos */
        $newPhotos = array_values((array) ($validated['photos'] ?? []));

        unset($validated['photo_order'], $validated['photos']);

        $directory = (string) config('properties.photos_directory');
        $disk = (string) config('properties.photos_disk');

        /** @var array<int, string> $removedPaths */
        $removedPaths = [];

        /** @var array<int, string> $storedPaths */
        $storedPaths = [];

        try {
            DB::transaction(function () use ($property, $validated, $photoOrder, $newPhotos, $directory, $disk, &$removedPaths, &$storedPaths): void {
                $operationChanged = $property->operation->value !== $validated['operation'];

                $property->update([
                    ...$validated,
                    ...($operationChanged ? ['status' => PropertyStatus::Available] : []),
                ]);

                $removed = $property->photos()->whereNotIn('id', $photoOrder)->get();

                $removedPaths = $removed->pluck('path')->all();

                $property->photos()->whereKey($removed->modelKeys())->delete();

                foreach ($photoOrder as $index => $photoId) {
                    $property->photos()
                        ->where('id', $photoId)
                        ->update(['position' => self::TEMPORARY_POSITION_OFFSET + $index]);
                }

                foreach ($photoOrder as $index => $photoId) {
                    $property->photos()->where('id', $photoId)->update(['position' => $index + 1]);
                }

                foreach ($newPhotos as $index => $photo) {
                    $path = $photo->storePublicly($directory, $disk);
                    $storedPaths[] = $path;

                    $property->photos()->create([
                        'path' => $path,
                        'position' => count($photoOrder) + $index + 1,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($storedPaths);

            throw $exception;
        }

        Storage::disk($disk)->delete($removedPaths);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Propiedad actualizada.',
        ]);

        return $property->isAvailable()
            ? to_route('properties.show', $property)
            : to_route('panel.properties.index');
    }

    public function show(Property $property): Response
    {
        $property->load('photos');

        return Inertia::render('properties/show', [
            'property' => (new PropertyResource($property))->resolve(),
        ]);
    }
}
