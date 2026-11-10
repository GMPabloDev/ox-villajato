<?php

namespace App\Http\Controllers;

use App\Http\Requests\PanelInquiryIndexRequest;
use App\Http\Requests\StorePropertyInquiryRequest;
use App\Mail\PropertyInquiry;
use App\Models\Property;
use App\Models\PropertyInquiry as PropertyInquiryModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;
use Throwable;

class PropertyInquiryController extends Controller
{
    public function index(PanelInquiryIndexRequest $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $canSeeAllInquiries = $user->isAdmin();
        $filters = $request->filters();

        $accessibleInquiries = PropertyInquiryModel::query()->visibleTo($user);

        $inquiries = $this->applyInquirySort(
            $this->applyInquiryFilters($accessibleInquiries->clone(), $filters)
                ->select(['id', 'property_id', 'property_title', 'publisher_id', 'name', 'email', 'phone', 'message', 'created_at'])
                ->when(
                    $canSeeAllInquiries,
                    fn (Builder $query): Builder => $query->with('publisher:id,name'),
                ),
            $filters['sort'],
        )
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PropertyInquiryModel $inquiry): array => [
                'id' => $inquiry->id,
                'property' => [
                    'title' => $inquiry->property_title,
                ],
                ...($canSeeAllInquiries ? [
                    'publisher' => [
                        'name' => $inquiry->publisher?->name,
                    ],
                ] : []),
                'name' => $inquiry->name,
                'email' => $inquiry->email,
                'phone' => $inquiry->phone,
                'message' => $inquiry->message,
                'received_at' => $inquiry->created_at?->toIso8601String(),
            ]);

        if ($inquiries->currentPage() > $inquiries->lastPage()) {
            return redirect()->to($inquiries->url($inquiries->lastPage()));
        }

        return Inertia::render('panel/inquiries/index', [
            'inquiries' => $inquiries,
            'canSeeAllInquiries' => $canSeeAllInquiries,
            'filters' => $filters,
            'accessibleTotal' => $accessibleInquiries->clone()->count(),
            'propertyOptions' => $this->propertyOptions($accessibleInquiries),
        ]);
    }

    /**
     * Apply the panel filters to the accessible inquiries.
     *
     * @param  Builder<PropertyInquiryModel>  $query
     * @param  array{search: string|null, property: int|null, sort: string}  $filters
     * @return Builder<PropertyInquiryModel>
     */
    private function applyInquiryFilters(Builder $query, array $filters): Builder
    {
        if ($filters['search'] !== null) {
            $query->search($filters['search']);
        }

        if ($filters['property'] !== null) {
            $query->where('property_id', $filters['property']);
        }

        return $query;
    }

    /**
     * Apply the panel ordering to the inquiries.
     *
     * @param  Builder<PropertyInquiryModel>  $query
     * @return Builder<PropertyInquiryModel>
     */
    private function applyInquirySort(Builder $query, string $sort): Builder
    {
        return $sort === 'oldest'
            ? $query->oldest('created_at')->oldest('id')
            : $query->latest('created_at')->latest('id');
    }

    /**
     * Build the property filter options from every accessible inquiry, so the
     * selector covers all pages and keeps properties that no longer exist.
     *
     * @param  Builder<PropertyInquiryModel>  $query
     * @return list<array{id: int, title: string}>
     */
    private function propertyOptions(Builder $query): array
    {
        $latestInquiryIds = $query->clone()
            ->toBase()
            ->selectRaw('MAX(id) as id')
            ->groupBy('property_id');

        return array_values(
            PropertyInquiryModel::query()
                ->toBase()
                ->whereIn('id', $latestInquiryIds)
                ->get(['property_id', 'property_title'])
                ->map(fn (stdClass $row): array => [
                    'id' => (int) $row->property_id,
                    'title' => (string) $row->property_title,
                ])
                ->sortBy(fn (array $option): array => [Str::lower(Str::ascii($option['title'])), $option['id']])
                ->values()
                ->all(),
        );
    }

    public function store(StorePropertyInquiryRequest $request, Property $property): RedirectResponse
    {
        $validated = $request->validated();

        $inquiry = $property->inquiries()->create([
            'property_title' => $property->title,
            'publisher_id' => $property->user_id,
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
