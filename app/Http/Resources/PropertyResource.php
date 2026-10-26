<?php

namespace App\Http\Resources;

use App\Models\Property;
use App\Models\PropertyPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Property
 */
class PropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'operation' => $this->operation->value,
            'operation_label' => $this->operation->label(),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'price' => $this->price,
            'price_formatted' => $this->priceFormatted(),
            'currency' => $this->currency->value,
            'city' => $this->city,
            'zone' => $this->zone,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'area' => $this->area,
            'cover_photo_url' => $this->photoUrl($this->photos->first()?->path),
            'photos' => $this->photos->map(fn (PropertyPhoto $photo): array => [
                'url' => $this->photoUrl($photo->path),
                'position' => $photo->position,
            ])->values()->all(),
            'url' => route('properties.show', $this->id),
        ];
    }

    private function priceFormatted(): string
    {
        $amount = (float) $this->price;
        $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;

        return $this->currency->symbol().' '.number_format($amount, $decimals, '.', ',');
    }

    private function photoUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        return Storage::disk((string) config('properties.photos_disk'))->url($path);
    }
}
