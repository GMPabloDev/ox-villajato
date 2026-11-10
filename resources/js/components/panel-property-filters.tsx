import {
    ArrowUpDown,
    Clock3,
    Search,
    SlidersHorizontal,
    X,
} from 'lucide-react';
import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    isPanelPropertySort,
    isPriceSort,
    PANEL_PRICE_RANGE_ERROR,
    panelPropertySortOptions,
    type PanelPropertyFiltersValue,
    type PanelPropertyStatusFilter,
    type PanelPropertyZonesByCity,
    withCurrentValue,
    zonesForCity,
} from '@/lib/panel-property-filters';
import {
    propertyCurrencyOptions,
    propertyOperationOptions,
    propertyTypeOptions,
    type PropertyCurrencyValue,
    type PropertyOperationValue,
    type PropertyTypeValue,
} from '@/lib/property-options';
import { cn } from '@/lib/utils';

export type PanelPropertyCounts = {
    all: number;
    available: number;
    sold: number;
    rented: number;
};

type SelectOption = {
    value: string;
    label: string;
    disabled?: boolean;
};

type DraftFilters = {
    operation: string;
    type: string;
    status: string;
    city: string;
    zone: string;
    currency: string;
    min_price: string;
    max_price: string;
    bedrooms: string;
};

const ALL = 'all';
const ANY = 'any';
const locationValue = (value: string) => `location:${value}`;
const locationFromValue = (value: string) => value.slice('location:'.length);

const operationOptions: SelectOption[] = propertyOperationOptions.map(
    (option) => ({ ...option }),
);

const typeOptions: SelectOption[] = propertyTypeOptions.map((option) => ({
    ...option,
}));

const currencyOptions: SelectOption[] = propertyCurrencyOptions.map(
    (option) => ({ ...option }),
);

const quickOperationOptions: SelectOption[] = [
    { value: ALL, label: 'Venta y alquiler' },
    ...operationOptions,
];

const quickTypeOptions: SelectOption[] = [
    { value: ALL, label: 'Todos los tipos' },
    ...typeOptions,
];

const drawerOperationOptions: SelectOption[] = [
    { value: ALL, label: 'Todas' },
    ...operationOptions,
];

const drawerTypeOptions: SelectOption[] = [
    { value: ALL, label: 'Todos' },
    ...typeOptions,
];

const drawerStatusOptions: SelectOption[] = [
    { value: ALL, label: 'Todos los estados' },
    { value: 'available', label: 'Disponible' },
    { value: 'sold', label: 'Vendida' },
    { value: 'rented', label: 'Alquilada' },
];

const drawerCurrencyOptions: SelectOption[] = [
    { value: ALL, label: 'Todas las monedas' },
    ...currencyOptions,
];

const bedroomOptions: SelectOption[] = [
    { value: ANY, label: 'Cualquier cantidad' },
    { value: '1', label: '1 o más' },
    { value: '2', label: '2 o más' },
    { value: '3', label: '3 o más' },
    { value: '4', label: '4 o más' },
];

const triggerClasses =
    'h-10 w-full rounded-[10px] border-input bg-card text-xs shadow-xs dark:bg-card';

const bareTriggerClasses =
    'h-8 max-w-[190px] border-0 bg-transparent px-1 text-xs shadow-none focus-visible:ring-0 dark:bg-transparent';

const priceInputClasses =
    'h-10 rounded-[10px] border-input bg-card text-xs disabled:cursor-not-allowed disabled:opacity-60';

function useHydrated(): boolean {
    const [isHydrated, setIsHydrated] = useState(false);

    useEffect(() => {
        setIsHydrated(true);
    }, []);

    return isHydrated;
}

function draftFromFilters(
    filters: PanelPropertyFiltersValue,
    zonesByCity: PanelPropertyZonesByCity,
): DraftFilters {
    const zone =
        filters.city !== null &&
        filters.zone !== null &&
        !zonesForCity(zonesByCity, filters.city).includes(filters.zone)
            ? null
            : filters.zone;

    return {
        operation: filters.operation ?? ALL,
        type: filters.type ?? ALL,
        status: filters.status ?? ALL,
        city: filters.city === null ? ALL : locationValue(filters.city),
        zone: zone === null ? ALL : locationValue(zone),
        currency: filters.currency ?? ALL,
        min_price: filters.min_price ?? '',
        max_price: filters.max_price ?? '',
        bedrooms: filters.bedrooms === null ? ANY : String(filters.bedrooms),
    };
}

function emptyToNull(value: string): string | null {
    const trimmed = value.trim();

    return trimmed === '' ? null : trimmed;
}

function parsePrice(value: string): number | null {
    const trimmed = value.trim();

    if (trimmed === '') {
        return null;
    }

    const parsed = Number(trimmed);

    return Number.isFinite(parsed) ? parsed : null;
}

function draftToFilters(
    draft: DraftFilters,
    filters: PanelPropertyFiltersValue,
): PanelPropertyFiltersValue {
    const currency =
        draft.currency === ALL
            ? null
            : (draft.currency as PropertyCurrencyValue);
    const sort =
        currency === null &&
        (filters.currency !== null || isPriceSort(filters.sort))
            ? 'recent'
            : filters.sort;

    return {
        ...filters,
        operation:
            draft.operation === ALL
                ? null
                : (draft.operation as PropertyOperationValue),
        type: draft.type === ALL ? null : (draft.type as PropertyTypeValue),
        status:
            draft.status === ALL
                ? null
                : (draft.status as PanelPropertyStatusFilter),
        city: draft.city === ALL ? null : locationFromValue(draft.city),
        zone: draft.zone === ALL ? null : locationFromValue(draft.zone),
        currency,
        min_price: currency === null ? null : emptyToNull(draft.min_price),
        max_price: currency === null ? null : emptyToNull(draft.max_price),
        bedrooms: draft.bedrooms === ANY ? null : Number(draft.bedrooms),
        sort,
    };
}

function SelectSkeleton({ className }: { className?: string }) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'block h-4 animate-pulse rounded bg-muted-foreground/15',
                className,
            )}
        />
    );
}

function FilterField({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <label className="grid min-w-0 gap-2 text-xs font-medium">
            <span>{label}</span>
            {children}
        </label>
    );
}

function FilterSelect({
    label,
    value,
    options,
    onValueChange,
}: {
    label: string;
    value: string;
    options: SelectOption[];
    onValueChange: (value: string) => void;
}) {
    return (
        <div className="grid min-w-0 gap-2 text-xs font-medium">
            <span>{label}</span>
            <Select value={value} onValueChange={onValueChange}>
                <SelectTrigger aria-label={label} className={triggerClasses}>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem
                            key={option.value}
                            value={option.value}
                            disabled={option.disabled}
                            className="text-xs"
                        >
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

function QuickFilter({
    label,
    value,
    options,
    isReady,
    skeletonClassName,
    className,
    onValueChange,
}: {
    label: string;
    value: string;
    options: SelectOption[];
    isReady: boolean;
    skeletonClassName: string;
    className?: string;
    onValueChange: (value: string) => void;
}) {
    return (
        <div
            className={cn(
                'flex h-9 min-w-0 items-center gap-2 rounded-[7px] border border-border pr-1 pl-2.5',
                className,
            )}
        >
            <span className="hidden text-[11px] whitespace-nowrap text-muted-foreground sm:inline">
                {label}
            </span>
            {isReady ? (
                <Select value={value} onValueChange={onValueChange}>
                    <SelectTrigger
                        aria-label={label}
                        className={bareTriggerClasses}
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {options.map((option) => (
                            <SelectItem
                                key={option.value}
                                value={option.value}
                                className="text-xs"
                            >
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ) : (
                <SelectSkeleton className={skeletonClassName} />
            )}
        </div>
    );
}

export function PanelPropertyFilters({
    filters,
    counts,
    cities,
    zonesByCity,
    priceRangeError = null,
    onApply,
}: {
    filters: PanelPropertyFiltersValue;
    counts: PanelPropertyCounts;
    cities: string[];
    zonesByCity: PanelPropertyZonesByCity;
    priceRangeError?: string | null;
    onApply: (filters: PanelPropertyFiltersValue) => void;
}) {
    const isHydrated = useHydrated();
    const [isFiltersOpen, setIsFiltersOpen] = useState(false);
    const [searchValue, setSearchValue] = useState(filters.search ?? '');
    const [draft, setDraft] = useState<DraftFilters>(() =>
        draftFromFilters(filters, zonesByCity),
    );
    const [priceError, setPriceError] = useState<string | null>(null);

    useEffect(() => {
        setSearchValue(filters.search ?? '');
    }, [filters.search]);

    const statusTabs = [
        { key: ALL, label: 'Todas', count: counts.all },
        { key: 'available', label: 'Disponibles', count: counts.available },
        { key: 'sold', label: 'Vendidas', count: counts.sold },
        { key: 'rented', label: 'Alquiladas', count: counts.rented },
    ];
    const activeStatus = filters.status ?? ALL;

    const cityOptions: SelectOption[] = [
        { value: ALL, label: 'Todas las ciudades' },
        ...withCurrentValue(cities, filters.city).map((city) => ({
            value: locationValue(city),
            label: city,
        })),
    ];

    const draftCityOptions: SelectOption[] = [
        { value: ALL, label: 'Todas las ciudades' },
        ...withCurrentValue(
            cities,
            draft.city === ALL ? null : locationFromValue(draft.city),
        ).map((city) => ({
            value: locationValue(city),
            label: city,
        })),
    ];

    const draftZoneOptions: SelectOption[] = [
        { value: ALL, label: 'Todas las zonas' },
        ...withCurrentValue(
            zonesForCity(
                zonesByCity,
                draft.city === ALL ? null : locationFromValue(draft.city),
            ),
            draft.city === ALL && draft.zone !== ALL
                ? locationFromValue(draft.zone)
                : null,
        ).map((zone) => ({
            value: locationValue(zone),
            label: zone,
        })),
    ];

    const sortOptions: SelectOption[] = panelPropertySortOptions.map(
        (option) => ({
            ...option,
            disabled: filters.currency === null && isPriceSort(option.value),
        }),
    );

    /**
     * The text in the search box applies together with any other control, so a
     * typed term is never left out of sight.
     */
    function pendingSearch(): string | null {
        return emptyToNull(searchValue);
    }

    function applyStatus(status: string) {
        onApply({
            ...filters,
            search: pendingSearch(),
            status:
                status === ALL ? null : (status as PanelPropertyStatusFilter),
        });
    }

    function changeSearch(value: string) {
        setSearchValue(value);

        if (value.trim() === '' && filters.search !== null) {
            onApply({ ...filters, search: null });
        }
    }

    function submitSearch(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const search = emptyToNull(searchValue);

        if (search !== filters.search) {
            onApply({ ...filters, search });
        }
    }

    function applyOperation(operation: string) {
        onApply({
            ...filters,
            search: pendingSearch(),
            operation:
                operation === ALL
                    ? null
                    : (operation as PropertyOperationValue),
        });
    }

    function applyType(type: string) {
        onApply({
            ...filters,
            search: pendingSearch(),
            type: type === ALL ? null : (type as PropertyTypeValue),
        });
    }

    function applyCity(city: string) {
        const nextCity = city === ALL ? null : locationFromValue(city);
        const keepZone =
            filters.zone !== null &&
            zonesForCity(zonesByCity, nextCity).includes(filters.zone);

        onApply({
            ...filters,
            search: pendingSearch(),
            city: nextCity,
            zone: keepZone ? filters.zone : null,
        });
    }

    function applySort(sort: string) {
        if (!isPanelPropertySort(sort)) {
            return;
        }

        if (filters.currency === null && isPriceSort(sort)) {
            return;
        }

        if (sort !== filters.sort) {
            onApply({ ...filters, search: pendingSearch(), sort });
        }
    }

    function changeFiltersOpen(open: boolean) {
        setIsFiltersOpen(open);

        if (open) {
            setDraft(draftFromFilters(filters, zonesByCity));
            setPriceError(priceRangeError);
        }
    }

    function updateDraft(changes: Partial<DraftFilters>) {
        setDraft((current) => ({ ...current, ...changes }));
        setPriceError(null);
    }

    function changeDraftCity(city: string) {
        setDraft((current) => {
            const keepZone =
                current.zone !== ALL &&
                zonesForCity(
                    zonesByCity,
                    city === ALL ? null : locationFromValue(city),
                ).includes(locationFromValue(current.zone));

            return {
                ...current,
                city,
                zone: keepZone ? current.zone : ALL,
            };
        });
        setPriceError(null);
    }

    function changeDraftCurrency(currency: string) {
        setDraft((current) => ({
            ...current,
            currency,
            min_price: currency === ALL ? '' : current.min_price,
            max_price: currency === ALL ? '' : current.max_price,
        }));
        setPriceError(null);
    }

    function applyDraft() {
        const minPrice = parsePrice(draft.min_price);
        const maxPrice = parsePrice(draft.max_price);

        if (
            draft.currency !== ALL &&
            ((minPrice !== null && minPrice < 0) ||
                (maxPrice !== null && maxPrice < 0))
        ) {
            setPriceError('Los precios deben ser mayores o iguales a cero.');

            return;
        }

        if (
            draft.currency !== ALL &&
            minPrice !== null &&
            maxPrice !== null &&
            minPrice > maxPrice
        ) {
            setPriceError(PANEL_PRICE_RANGE_ERROR);

            return;
        }

        setIsFiltersOpen(false);
        setPriceError(null);
        onApply({
            ...draftToFilters(draft, filters),
            search: pendingSearch(),
        });
    }

    return (
        <>
            <div className="flex items-center justify-between gap-3 border-b border-border px-4 md:px-6">
                <div
                    role="group"
                    aria-label="Filtrar por estado"
                    className="-mb-px flex min-w-max gap-5 overflow-x-auto md:gap-6"
                >
                    {statusTabs.map((tab) => {
                        const isActive = tab.key === activeStatus;

                        return (
                            <button
                                key={tab.key}
                                type="button"
                                onClick={() => applyStatus(tab.key)}
                                aria-pressed={isActive}
                                className={cn(
                                    'flex min-h-[58px] cursor-pointer items-center gap-1.5 border-b-2 pt-1 pb-0.5 text-xs transition-colors',
                                    isActive
                                        ? 'border-primary font-semibold text-primary'
                                        : 'border-transparent text-muted-foreground hover:text-primary',
                                )}
                            >
                                {tab.label}
                                <span
                                    className={cn(
                                        'min-w-5 rounded-[5px] px-1.5 py-0.5 text-[10px]',
                                        isActive
                                            ? 'bg-secondary text-secondary-foreground'
                                            : 'bg-muted text-muted-foreground',
                                    )}
                                >
                                    {tab.count}
                                </span>
                            </button>
                        );
                    })}
                </div>

                <span className="hidden shrink-0 text-[10px] text-muted-foreground sm:block">
                    Inventario actual
                </span>
            </div>

            <div className="flex flex-col gap-2.5 px-4 pt-4 pb-3 md:flex-row md:items-center md:px-6">
                <form
                    role="search"
                    onSubmit={submitSearch}
                    className="flex h-11 min-w-0 flex-1 items-center gap-2.5 rounded-[10px] border border-[#cbd8ce] bg-card px-3 text-muted-foreground focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50 dark:border-border"
                >
                    <Search className="size-4 shrink-0" aria-hidden="true" />
                    <label htmlFor="panel-property-search" className="sr-only">
                        Buscar por título, ciudad o zona
                    </label>
                    <Input
                        id="panel-property-search"
                        name="search"
                        type="search"
                        value={searchValue}
                        onChange={(event) => changeSearch(event.target.value)}
                        autoComplete="off"
                        placeholder="Buscar por título, ciudad o zona…"
                        className="h-9 border-0 bg-transparent px-0 text-xs text-foreground shadow-none focus-visible:ring-0"
                    />
                    <kbd
                        aria-hidden="true"
                        className="hidden size-5 shrink-0 place-items-center rounded-[4px] border border-border text-[11px] md:grid"
                    >
                        /
                    </kbd>
                </form>

                <Sheet open={isFiltersOpen} onOpenChange={changeFiltersOpen}>
                    <SheetTrigger asChild>
                        <Button
                            type="button"
                            variant="outline"
                            className="h-11 shrink-0 gap-2 rounded-[10px] border-[#cfdcd4] bg-card px-4 text-xs font-semibold dark:border-border"
                        >
                            <SlidersHorizontal
                                className="size-4"
                                aria-hidden="true"
                            />
                            Filtros
                        </Button>
                    </SheetTrigger>

                    <SheetContent
                        side="right"
                        showCloseButton={false}
                        className="flex w-full flex-col gap-0 p-0 sm:max-w-[460px]"
                    >
                        <SheetHeader className="flex-row items-start justify-between gap-3 border-b border-border px-5 py-5 text-left sm:px-6">
                            <div className="min-w-0">
                                <p className="text-[10px] font-semibold tracking-[0.16em] text-muted-foreground">
                                    AFINA TU BÚSQUEDA
                                </p>
                                <SheetTitle className="mt-2 font-display text-[23px] font-extrabold tracking-[-0.6px]">
                                    Filtrar propiedades
                                </SheetTitle>
                                <SheetDescription className="mt-2 text-xs leading-relaxed">
                                    Combina criterios para encontrar un
                                    inmueble.
                                </SheetDescription>
                            </div>

                            <SheetClose asChild>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="icon"
                                    className="size-9 shrink-0 rounded-[9px] text-muted-foreground"
                                    aria-label="Cerrar filtros"
                                >
                                    <X className="size-4" aria-hidden="true" />
                                </Button>
                            </SheetClose>
                        </SheetHeader>

                        <div className="min-h-0 flex-1 space-y-6 overflow-y-auto px-5 py-5 sm:px-6">
                            <p className="flex items-start gap-2 rounded-lg border border-border bg-secondary/60 px-3 py-2.5 text-[11px] leading-relaxed text-secondary-foreground">
                                <Clock3
                                    className="mt-0.5 size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                <span>
                                    Ajusta los criterios y pulsa «Mostrar
                                    resultados» para aplicarlos. Si cierras el
                                    panel, los cambios se descartan.
                                </span>
                            </p>

                            <fieldset className="space-y-4 border-b border-border pb-6">
                                <legend className="font-display text-sm font-extrabold tracking-[-0.3px]">
                                    Inmueble
                                </legend>

                                <div className="grid grid-cols-2 gap-3">
                                    <FilterSelect
                                        label="Operación"
                                        value={draft.operation}
                                        options={drawerOperationOptions}
                                        onValueChange={(value) =>
                                            updateDraft({ operation: value })
                                        }
                                    />

                                    <FilterSelect
                                        label="Tipo"
                                        value={draft.type}
                                        options={drawerTypeOptions}
                                        onValueChange={(value) =>
                                            updateDraft({ type: value })
                                        }
                                    />
                                </div>

                                <FilterSelect
                                    label="Estado"
                                    value={draft.status}
                                    options={drawerStatusOptions}
                                    onValueChange={(value) =>
                                        updateDraft({ status: value })
                                    }
                                />
                            </fieldset>

                            <fieldset className="space-y-4 border-b border-border pb-6">
                                <legend className="font-display text-sm font-extrabold tracking-[-0.3px]">
                                    Ubicación
                                </legend>

                                <div className="grid grid-cols-2 gap-3">
                                    <FilterSelect
                                        label="Ciudad"
                                        value={draft.city}
                                        options={draftCityOptions}
                                        onValueChange={changeDraftCity}
                                    />

                                    <FilterSelect
                                        label="Zona"
                                        value={draft.zone}
                                        options={draftZoneOptions}
                                        onValueChange={(value) =>
                                            updateDraft({ zone: value })
                                        }
                                    />
                                </div>
                            </fieldset>

                            <fieldset className="space-y-4 border-b border-border pb-6">
                                <legend className="font-display text-sm font-extrabold tracking-[-0.3px]">
                                    Precio
                                </legend>

                                <FilterSelect
                                    label="Moneda"
                                    value={draft.currency}
                                    options={drawerCurrencyOptions}
                                    onValueChange={changeDraftCurrency}
                                />

                                <p className="text-[10px] leading-relaxed text-muted-foreground">
                                    Elige una moneda para filtrar por precio. No
                                    convertimos importes.
                                </p>

                                <div className="grid grid-cols-2 gap-3">
                                    <FilterField label="Desde">
                                        <Input
                                            type="number"
                                            inputMode="numeric"
                                            min={0}
                                            name="min_price"
                                            value={draft.min_price}
                                            onChange={(event) =>
                                                updateDraft({
                                                    min_price:
                                                        event.target.value,
                                                })
                                            }
                                            disabled={draft.currency === ALL}
                                            aria-invalid={priceError !== null}
                                            aria-describedby={
                                                priceError !== null
                                                    ? 'panel-price-range-error'
                                                    : undefined
                                            }
                                            placeholder="Sin mínimo"
                                            className={priceInputClasses}
                                        />
                                    </FilterField>

                                    <FilterField label="Hasta">
                                        <Input
                                            type="number"
                                            inputMode="numeric"
                                            min={0}
                                            name="max_price"
                                            value={draft.max_price}
                                            onChange={(event) =>
                                                updateDraft({
                                                    max_price:
                                                        event.target.value,
                                                })
                                            }
                                            disabled={draft.currency === ALL}
                                            aria-invalid={priceError !== null}
                                            aria-describedby={
                                                priceError !== null
                                                    ? 'panel-price-range-error'
                                                    : undefined
                                            }
                                            placeholder="Sin máximo"
                                            className={priceInputClasses}
                                        />
                                    </FilterField>
                                </div>

                                <InputError
                                    id="panel-price-range-error"
                                    message={priceError ?? undefined}
                                    className="text-[11px]"
                                />
                            </fieldset>

                            <fieldset className="space-y-4">
                                <legend className="font-display text-sm font-extrabold tracking-[-0.3px]">
                                    Características
                                </legend>

                                <FilterSelect
                                    label="Dormitorios"
                                    value={draft.bedrooms}
                                    options={bedroomOptions}
                                    onValueChange={(value) =>
                                        updateDraft({ bedrooms: value })
                                    }
                                />
                            </fieldset>
                        </div>

                        <SheetFooter className="flex-row gap-2 border-t border-border px-5 py-4 sm:px-6">
                            <Button
                                type="button"
                                variant="outline"
                                className="h-10 flex-1 rounded-[10px] text-xs font-semibold"
                                onClick={() => setIsFiltersOpen(false)}
                            >
                                Cerrar
                            </Button>
                            <Button
                                type="button"
                                className="h-10 flex-[1.4] rounded-[10px] text-xs font-semibold"
                                onClick={applyDraft}
                            >
                                Mostrar resultados
                            </Button>
                        </SheetFooter>
                    </SheetContent>
                </Sheet>

                <div className="flex h-11 shrink-0 items-center gap-2 rounded-[10px] border border-[#cbd8ce] bg-card pr-1 pl-3 text-muted-foreground focus-within:ring-[3px] focus-within:ring-ring/50 dark:border-border">
                    <ArrowUpDown className="size-4" aria-hidden="true" />
                    {isHydrated ? (
                        <Select value={filters.sort} onValueChange={applySort}>
                            <SelectTrigger
                                aria-label="Ordenar propiedades"
                                className="h-9 w-auto border-0 bg-transparent px-0 pr-2 text-xs text-foreground shadow-none focus-visible:ring-0 dark:bg-transparent"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {sortOptions.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                        disabled={option.disabled}
                                        className="text-xs"
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    ) : (
                        <SelectSkeleton className="w-[112px]" />
                    )}
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-2 px-4 pb-4 md:px-6">
                <QuickFilter
                    label="Operación"
                    value={filters.operation ?? ALL}
                    options={quickOperationOptions}
                    isReady={isHydrated}
                    skeletonClassName="w-[119px]"
                    className="flex-1 basis-[calc(50%-0.25rem)] sm:flex-none sm:basis-auto"
                    onValueChange={applyOperation}
                />

                <QuickFilter
                    label="Tipo de inmueble"
                    value={filters.type ?? ALL}
                    options={quickTypeOptions}
                    isReady={isHydrated}
                    skeletonClassName="w-[118px]"
                    className="flex-1 basis-[calc(50%-0.25rem)] sm:flex-none sm:basis-auto"
                    onValueChange={applyType}
                />

                <QuickFilter
                    label="Ciudad"
                    value={
                        filters.city === null
                            ? ALL
                            : locationValue(filters.city)
                    }
                    options={cityOptions}
                    isReady={isHydrated}
                    skeletonClassName="w-[141px]"
                    className="hidden sm:flex"
                    onValueChange={applyCity}
                />
            </div>
        </>
    );
}
