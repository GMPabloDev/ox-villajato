<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function index(): Response
    {
        $properties = Property::query()
            ->with('photos')
            ->latest('id')
            ->get();

        return Inertia::render('properties/index', [
            'properties' => $properties
                ->map(fn (Property $property): array => (new PropertyResource($property))->resolve())
                ->all(),
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
