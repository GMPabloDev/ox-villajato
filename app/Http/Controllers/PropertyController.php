<?php

namespace App\Http\Controllers;

use App\Enums\PropertyOperation;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Http\Requests\PanelPropertyIndexRequest;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Department;
use App\Models\District;
use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Models\Province;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
                'department_code' => ['nullable', 'string', Rule::exists('departments', 'code')],
                'province_code' => ['nullable', 'string', Rule::exists('provinces', 'code')],
                'district_code' => ['nullable', 'string', Rule::exists('districts', 'code')],
            ],
            [
                'operation.enum' => 'El valor seleccionado para operación no es válido.',
                'type.enum' => 'El valor seleccionado para tipo no es válido.',
                'department_code.exists' => 'El valor seleccionado para departamento no es válido.',
                'province_code.exists' => 'El valor seleccionado para provincia no es válido.',
                'district_code.exists' => 'El valor seleccionado para distrito no es válido.',
            ],
            [
                'operation' => 'operación',
                'type' => 'tipo',
                'department_code' => 'departamento',
                'province_code' => 'provincia',
                'district_code' => 'distrito',
            ],
        );

        if ($validator->fails()) {
            return redirect()
                ->route('home')
                ->withErrors($validator);
        }

        /**
         * @var array{
         *     operation?: string|null,
         *     type?: string|null,
         *     department_code?: string|null,
         *     province_code?: string|null,
         *     district_code?: string|null
         * } $filters
         */
        $filters = $validator->validated();
        $location = $this->catalogLocation(
            $filters['department_code'] ?? null,
            $filters['province_code'] ?? null,
            $filters['district_code'] ?? null,
        );

        $properties = $this->applyLocationFilters(
            Property::query()
                ->available()
                ->with('photos', 'district.province.department')
                ->when(
                    $filters['operation'] ?? null,
                    fn (Builder $query, string $operation): Builder => $query->where('operation', $operation),
                )
                ->when(
                    $filters['type'] ?? null,
                    fn (Builder $query, string $type): Builder => $query->where('type', $type),
                ),
            $location['department_code'],
            $location['province_code'],
            $location['district_code'],
        )
            ->latest('id')
            ->get();

        return Inertia::render('properties/index', [
            'properties' => $properties
                ->map(fn (Property $property): array => (new PropertyResource($property))->resolve())
                ->all(),
            'filters' => [
                'operation' => $filters['operation'] ?? null,
                'type' => $filters['type'] ?? null,
                'department_code' => $location['department_code'],
                'province_code' => $location['province_code'],
                'district_code' => $location['district_code'],
            ],
            'locations' => $this->catalogLocations(),
            'appliedLocation' => $location['applied'],
        ]);
    }

    /**
     * Resolve the selected location levels into a coherent chain and the names
     * the catalog displays for them.
     *
     * A district fills its province and department, so an existing district
     * link keeps working. A level that does not belong to the level above it is
     * dropped, keeping the highest valid level. The names stay available when
     * the selected location no longer has available properties.
     *
     * @return array{
     *     department_code: string|null,
     *     province_code: string|null,
     *     district_code: string|null,
     *     applied: array{department: string|null, province: string|null, district: string|null}
     * }
     */
    private function catalogLocation(
        ?string $departmentCode,
        ?string $provinceCode,
        ?string $districtCode,
    ): array {
        $district = $districtCode === null
            ? null
            : District::query()->with('province.department')->find($districtCode);

        if ($district !== null) {
            $fitsDepartment = $departmentCode === null || $district->province->department_code === $departmentCode;
            $fitsProvince = $provinceCode === null || $district->province_code === $provinceCode;

            if ($fitsDepartment && $fitsProvince) {
                return [
                    'department_code' => $district->province->department_code,
                    'province_code' => $district->province_code,
                    'district_code' => $district->code,
                    'applied' => [
                        'department' => $district->province->department->name,
                        'province' => $district->province->name,
                        'district' => $district->name,
                    ],
                ];
            }
        }

        $province = $provinceCode === null
            ? null
            : Province::query()->with('department')->find($provinceCode);

        if ($province !== null && ($departmentCode === null || $province->department_code === $departmentCode)) {
            return [
                'department_code' => $province->department_code,
                'province_code' => $province->code,
                'district_code' => null,
                'applied' => [
                    'department' => $province->department->name,
                    'province' => $province->name,
                    'district' => null,
                ],
            ];
        }

        $department = $departmentCode === null
            ? null
            : Department::query()->find($departmentCode);

        return [
            'department_code' => $department?->code,
            'province_code' => null,
            'district_code' => null,
            'applied' => [
                'department' => $department?->name,
                'province' => null,
                'district' => null,
            ],
        ];
    }

    /**
     * Locations with available properties offered by the public filters, from
     * the department down to the district. The options never depend on the
     * operation, the type or the selected location, and a district carries its
     * province and department so homonyms stay distinguishable.
     *
     * @return array{
     *     departments: list<array{code: string, name: string}>,
     *     provinces: list<array{code: string, name: string, department_code: string}>,
     *     districts: list<array{code: string, name: string, province_code: string, province: string, department: string}>
     * }
     */
    private function catalogLocations(): array
    {
        /** @var list<string> $codes */
        $codes = Property::query()
            ->available()
            ->toBase()
            ->distinct()
            ->pluck('district_code')
            ->all();

        $districts = District::query()
            ->whereIn('code', $codes)
            ->with('province.department')
            ->get()
            ->sortBy(fn (District $district): string => Str::lower(Str::ascii(
                $district->name.', '.$district->province->name.' '.$district->province->department->name,
            )))
            ->values();

        /** @var Collection<string, Province> $provinces */
        $provinces = $districts
            ->map(fn (District $district): Province => $district->province)
            ->keyBy('code');

        /** @var Collection<string, Department> $departments */
        $departments = $provinces
            ->map(fn (Province $province): Department => $province->department)
            ->keyBy('code');

        return [
            'departments' => $this->sortLocationOptions(
                $departments->map(fn (Department $department): array => [
                    'code' => $department->code,
                    'name' => $department->name,
                ])->all(),
            ),
            'provinces' => $this->sortLocationOptions(
                $provinces->map(fn (Province $province): array => [
                    'code' => $province->code,
                    'name' => $province->name,
                    'department_code' => $province->department_code,
                ])->all(),
            ),
            'districts' => array_values(
                $districts
                    ->map(fn (District $district): array => [
                        'code' => $district->code,
                        'name' => $district->name,
                        'province_code' => $district->province_code,
                        'province' => $district->province->name,
                        'department' => $district->province->department->name,
                    ])
                    ->all(),
            ),
        ];
    }

    public function panelIndex(PanelPropertyIndexRequest $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $disk = (string) config('properties.photos_disk');
        $filters = $request->filters();

        $accessibleQuery = Property::query()->notArchived()->visibleTo($user);

        $properties = $this->applyPanelSort(
            $this->applyPanelFilters($accessibleQuery->clone(), $filters, includeStatus: true)
                ->with('photos', 'district.province.department'),
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

        $priceRangeError = $request->priceRangeError();

        return Inertia::render('panel/properties/index', [
            'properties' => $properties,
            'filters' => $filters,
            'counts' => $counts,
            'locations' => $this->panelLocations($accessibleQuery),
            'appliedLocation' => [
                'department' => isset($filters['department_code']) ? Department::query()->find($filters['department_code'])?->name : null,
                'province' => isset($filters['province_code']) ? Province::query()->find($filters['province_code'])?->name : null,
                'district' => isset($filters['district_code']) ? District::query()->find($filters['district_code'])?->name : null,
            ],
            'filterErrors' => $priceRangeError === null ? [] : ['price' => $priceRangeError],
        ]);
    }

    /**
     * Get the locations present in the properties the user can manage.
     *
     * @param  Builder<Property>  $accessibleQuery
     * @return array{
     *     departments: list<array{code: string, name: string}>,
     *     provinces: list<array{code: string, name: string, department_code: string}>,
     *     districts: list<array{code: string, name: string, province_code: string, province: string}>
     * }
     */
    private function panelLocations(Builder $accessibleQuery): array
    {
        /** @var list<string> $codes */
        $codes = $accessibleQuery->clone()
            ->toBase()
            ->distinct()
            ->pluck('district_code')
            ->all();

        $districts = District::query()
            ->whereIn('code', $codes)
            ->with('province.department')
            ->get();

        /** @var Collection<string, Province> $provinces */
        $provinces = $districts
            ->map(fn (District $district): Province => $district->province)
            ->keyBy('code');

        /** @var Collection<string, Department> $departments */
        $departments = $provinces
            ->map(fn (Province $province): Department => $province->department)
            ->keyBy('code');

        return [
            'departments' => $this->sortLocationOptions(
                $departments->map(fn (Department $department): array => [
                    'code' => $department->code,
                    'name' => $department->name,
                ])->all(),
            ),
            'provinces' => $this->sortLocationOptions(
                $provinces->map(fn (Province $province): array => [
                    'code' => $province->code,
                    'name' => $province->name,
                    'department_code' => $province->department_code,
                ])->all(),
            ),
            'districts' => $this->sortLocationOptions(
                $districts->map(fn (District $district): array => [
                    'code' => $district->code,
                    'name' => $district->name,
                    'province_code' => $district->province_code,
                    'province' => $district->province->name,
                ])->all(),
            ),
        ];
    }

    /**
     * Order location options by name, ignoring case and accents.
     *
     * @template TLocation of array{name: string}
     *
     * @param  array<array-key, TLocation>  $options
     * @return list<TLocation>
     */
    private function sortLocationOptions(array $options): array
    {
        usort(
            $options,
            fn (array $first, array $second): int => strcasecmp(Str::ascii($first['name']), Str::ascii($second['name'])),
        );

        return $options;
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
     *     department_code: string|null,
     *     province_code: string|null,
     *     district_code: string|null,
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

        $query = $this->applyLocationFilters(
            $query,
            $filters['department_code'],
            $filters['province_code'],
            $filters['district_code'],
        );

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
     * Apply the selected location levels to the query. Each level works on its
     * own and they can be combined.
     *
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    private function applyLocationFilters(
        Builder $query,
        ?string $department,
        ?string $province,
        ?string $district,
    ): Builder {
        if ($district !== null) {
            $query->where('district_code', $district);
        }

        if ($province !== null) {
            $query->whereIn('district_code', District::query()
                ->select('code')
                ->where('province_code', $province));
        }

        if ($department !== null) {
            $query->whereIn('district_code', District::query()
                ->select('code')
                ->whereIn('province_code', Province::query()
                    ->select('code')
                    ->where('department_code', $department)));
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

    public function archivedIndex(Request $request): Response
    {
        $user = $request->user();

        $properties = Property::query()
            ->where('status', PropertyStatus::Archived)
            ->when(! $user->isAdmin(), fn (Builder $query): Builder => $query->whereBelongsTo($user))
            ->with('photos', 'district.province.department')
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

        unset($validated['photos'], $validated['department_code'], $validated['province_code']);

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

        unset($validated['photo_order'], $validated['photos'], $validated['department_code'], $validated['province_code']);

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
        $property->load('photos', 'district.province.department');

        return Inertia::render('properties/show', [
            'property' => (new PropertyResource($property))->resolve(),
        ]);
    }
}
