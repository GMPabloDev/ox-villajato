<?php

namespace Database\Seeders;

use App\Models\Property;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ClearSamplePropertiesSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Property::query()
            ->whereHas('photos', fn (Builder $query): Builder => $query
                ->where('path', 'like', SamplePropertiesSeeder::PHOTO_PATH_PREFIX.'%'))
            ->delete();

        Storage::disk((string) config('properties.photos_disk'))
            ->deleteDirectory(SamplePropertiesSeeder::PHOTO_DIRECTORY);
    }
}
