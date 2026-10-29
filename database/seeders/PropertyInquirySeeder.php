<?php

namespace Database\Seeders;

use App\Models\Property;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class PropertyInquirySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Property::query()
            ->available()
            ->whereHas('photos', fn (Builder $query): Builder => $query
                ->where('path', 'like', SamplePropertiesSeeder::PHOTO_PATH_PREFIX.'%'))
            ->each(function (Property $property): void {
                $property->inquiries()->updateOrCreate(
                    ['email' => 'consulta-ejemplo@example.test'],
                    [
                        'name' => 'Visitante de ejemplo',
                        'phone' => '+51 987 654 321',
                        'message' => 'Quisiera coordinar una visita.',
                    ],
                );
            });
    }
}
