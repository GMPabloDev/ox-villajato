<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyInquiryRequest;
use App\Mail\PropertyInquiry;
use App\Models\Property;
use App\Models\PropertyInquiry as PropertyInquiryModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PropertyInquiryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $inquiries = PropertyInquiryModel::query()
            ->select(['id', 'property_id', 'name', 'email', 'phone', 'message', 'created_at'])
            ->whereHas('property', fn (Builder $query): Builder => $query->whereBelongsTo($user))
            ->with('property:id,title')
            ->latest()
            ->paginate(15)
            ->through(fn (PropertyInquiryModel $inquiry): array => [
                'id' => $inquiry->id,
                'property' => [
                    'title' => $inquiry->property->title,
                ],
                'name' => $inquiry->name,
                'email' => $inquiry->email,
                'phone' => $inquiry->phone,
                'message' => $inquiry->message,
                'received_at' => $inquiry->created_at?->toIso8601String(),
            ]);

        return Inertia::render('panel/inquiries/index', [
            'inquiries' => $inquiries,
        ]);
    }

    public function store(StorePropertyInquiryRequest $request, Property $property): RedirectResponse
    {
        $validated = $request->validated();

        $inquiry = $property->inquiries()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'],
        ]);

        try {
            Mail::send(new PropertyInquiry(
                property: $property,
                visitorName: $inquiry->name,
                visitorEmail: $inquiry->email,
                visitorPhone: $inquiry->phone,
                visitorMessage: $inquiry->message,
            ));
        } catch (Throwable $exception) {
            report($exception);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tu consulta fue enviada. Nos pondremos en contacto contigo pronto.',
        ]);

        return to_route('properties.show', $property);
    }
}
