<?php

namespace App\Http\Controllers;

use App\Enums\PropertyOperation;
use App\Enums\PropertyType;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
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

        $zones = Property::query()
            ->distinct()
            ->pluck('zone')
            ->sortBy(fn (string $zone): string => Str::lower(Str::ascii($zone)))
            ->values()
            ->all();

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

    public function create(): Response
    {
        return Inertia::render('properties/create');
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var array<int, UploadedFile> $photos */
        $photos = $validated['photos'];

        unset($validated['photos']);

        $property = Property::create($validated);

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

    public function show(Property $property): Response
    {
        $property->load('photos');

        return Inertia::render('properties/show', [
            'property' => (new PropertyResource($property))->resolve(),
        ]);
    }
}
