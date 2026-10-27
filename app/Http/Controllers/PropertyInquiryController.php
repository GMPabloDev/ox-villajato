<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyInquiryRequest;
use App\Mail\PropertyInquiry;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class PropertyInquiryController extends Controller
{
    public function store(StorePropertyInquiryRequest $request, Property $property): RedirectResponse
    {
        $validated = $request->validated();

        Mail::send(new PropertyInquiry(
            property: $property,
            visitorName: (string) $validated['name'],
            visitorEmail: (string) $validated['email'],
            visitorPhone: isset($validated['phone']) ? (string) $validated['phone'] : null,
            visitorMessage: (string) $validated['message'],
        ));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tu consulta fue enviada. Nos pondremos en contacto contigo pronto.',
        ]);

        return to_route('properties.show', $property);
    }
}
