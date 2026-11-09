import {
    ArrowUpDown,
    Clock3,
    Search,
    SlidersHorizontal,
    X,
} from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
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
    propertyCurrencyOptions,
    propertyOperationOptions,
    propertyTypeOptions,
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
};

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
    { value: 'all', label: 'Venta y alquiler' },
    ...operationOptions,
];

const quickTypeOptions: SelectOption[] = [
    { value: 'all', label: 'Todos los tipos' },
    ...typeOptions,
];

const drawerOperationOptions: SelectOption[] = [
    { value: 'all', label: 'Todas' },
    ...operationOptions,
];

const drawerTypeOptions: SelectOption[] = [
    { value: 'all', label: 'Todos' },
    ...typeOptions,
];

const statusOptions: SelectOption[] = [
    { value: 'all', label: 'Todos los estados' },
    { value: 'available', label: 'Disponible' },
    { value: 'sold', label: 'Vendida' },
    { value: 'rented', label: 'Alquilada' },
];

const drawerCurrencyOptions: SelectOption[] = [
    { value: 'all', label: 'Todas las monedas' },
    ...currencyOptions,
];

const bedroomOptions: SelectOption[] = [
    { value: 'any', label: 'Cualquier cantidad' },
    { value: '1', label: '1 o más' },
    { value: '2', label: '2 o más' },
    { value: '3', label: '3 o más' },
    { value: '4', label: '4 o más' },
];

const sortOptions: SelectOption[] = [
    { value: 'recent', label: 'Más recientes' },
    { value: 'title', label: 'Título A–Z' },
    { value: 'price-asc', label: 'Precio: menor primero' },
    { value: 'price-desc', label: 'Precio: mayor primero' },
];

const triggerClasses =
    'h-10 w-full rounded-[10px] border-input bg-card text-xs shadow-xs dark:bg-card';

const bareTriggerClasses =
    'h-8 max-w-[190px] border-0 bg-transparent px-1 text-xs shadow-none focus-visible:ring-0 dark:bg-transparent';

function useHydrated(): boolean {
    const [isHydrated, setIsHydrated] = useState(false);

    useEffect(() => {
        setIsHydrated(true);
    }, []);

    return isHydrated;
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
    defaultValue,
    options,
}: {
    label: string;
    defaultValue: string;
    options: SelectOption[];
}) {
    return (
        <div className="grid min-w-0 gap-2 text-xs font-medium">
            <span>{label}</span>
            <Select defaultValue={defaultValue}>
                <SelectTrigger aria-label={label} className={triggerClasses}>
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
        </div>
    );
}

function QuickFilter({
    label,
    defaultValue,
    options,
    isReady,
    skeletonClassName,
    className,
}: {
    label: string;
    defaultValue: string;
    options: SelectOption[];
    isReady: boolean;
    skeletonClassName: string;
    className?: string;
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
                <Select defaultValue={defaultValue}>
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
    counts,
    cities,
    zones,
}: {
    counts: PanelPropertyCounts;
    cities: string[];
    zones: string[];
}) {
    const isHydrated = useHydrated();
    const [isFiltersOpen, setIsFiltersOpen] = useState(false);
    const [activeStatus, setActiveStatus] = useState('all');

    const statusTabs = [
        { key: 'all', label: 'Todas', count: counts.all },
        { key: 'available', label: 'Disponibles', count: counts.available },
        { key: 'sold', label: 'Vendidas', count: counts.sold },
        { key: 'rented', label: 'Alquiladas', count: counts.rented },
    ];

    const cityOptions: SelectOption[] = [
        { value: 'all', label: 'Todas las ciudades' },
        ...cities.map((city) => ({ value: city, label: city })),
    ];

    const zoneOptions: SelectOption[] = [
        { value: 'all', label: 'Todas las zonas' },
        ...zones.map((zone) => ({ value: zone, label: zone })),
    ];

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
                                onClick={() => setActiveStatus(tab.key)}
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
                <label className="flex h-11 min-w-0 flex-1 items-center gap-2.5 rounded-[10px] border border-[#cbd8ce] bg-card px-3 text-muted-foreground focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50 dark:border-border">
                    <Search className="size-4 shrink-0" aria-hidden="true" />
                    <span className="sr-only">
                        Buscar por título, código o ubicación
                    </span>
                    <Input
                        type="search"
                        placeholder="Buscar por título, código o ubicación…"
                        className="h-9 border-0 bg-transparent px-0 text-xs text-foreground shadow-none focus-visible:ring-0"
                    />
                    <kbd
                        aria-hidden="true"
                        className="hidden size-5 shrink-0 place-items-center rounded-[4px] border border-border text-[11px] md:grid"
                    >
                        /
                    </kbd>
                </label>

                <Sheet open={isFiltersOpen} onOpenChange={setIsFiltersOpen}>
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
                                    Puedes ajustar los filtros, pero todavía no
                                    modifican el inventario. Así se verán cuando
                                    estén activos.
                                </span>
                            </p>

                            <fieldset className="space-y-4 border-b border-border pb-6">
                                <legend className="font-display text-sm font-extrabold tracking-[-0.3px]">
                                    Inmueble
                                </legend>

                                <div className="grid grid-cols-2 gap-3">
                                    <FilterSelect
                                        label="Operación"
                                        defaultValue="all"
                                        options={drawerOperationOptions}
                                    />

                                    <FilterSelect
                                        label="Tipo"
                                        defaultValue="all"
                                        options={drawerTypeOptions}
                                    />
                                </div>

                                <FilterSelect
                                    label="Estado"
                                    defaultValue="all"
                                    options={statusOptions}
                                />
                            </fieldset>

                            <fieldset className="space-y-4 border-b border-border pb-6">
                                <legend className="font-display text-sm font-extrabold tracking-[-0.3px]">
                                    Ubicación
                                </legend>

                                <div className="grid grid-cols-2 gap-3">
                                    <FilterSelect
                                        label="Ciudad"
                                        defaultValue="all"
                                        options={cityOptions}
                                    />

                                    <FilterSelect
                                        label="Zona"
                                        defaultValue="all"
                                        options={zoneOptions}
                                    />
                                </div>
                            </fieldset>

                            <fieldset className="space-y-4 border-b border-border pb-6">
                                <legend className="font-display text-sm font-extrabold tracking-[-0.3px]">
                                    Precio
                                </legend>

                                <FilterSelect
                                    label="Moneda"
                                    defaultValue="all"
                                    options={drawerCurrencyOptions}
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
                                            placeholder="Sin mínimo"
                                            className="h-10 rounded-[10px] border-input bg-card text-xs"
                                        />
                                    </FilterField>

                                    <FilterField label="Hasta">
                                        <Input
                                            type="number"
                                            inputMode="numeric"
                                            placeholder="Sin máximo"
                                            className="h-10 rounded-[10px] border-input bg-card text-xs"
                                        />
                                    </FilterField>
                                </div>
                            </fieldset>

                            <fieldset className="space-y-4">
                                <legend className="font-display text-sm font-extrabold tracking-[-0.3px]">
                                    Características
                                </legend>

                                <FilterSelect
                                    label="Dormitorios"
                                    defaultValue="any"
                                    options={bedroomOptions}
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
                                onClick={() => setIsFiltersOpen(false)}
                            >
                                Mostrar resultados
                            </Button>
                        </SheetFooter>
                    </SheetContent>
                </Sheet>

                <div className="flex h-11 shrink-0 items-center gap-2 rounded-[10px] border border-[#cbd8ce] bg-card pr-1 pl-3 text-muted-foreground focus-within:ring-[3px] focus-within:ring-ring/50 dark:border-border">
                    <ArrowUpDown className="size-4" aria-hidden="true" />
                    {isHydrated ? (
                        <Select defaultValue="recent">
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
                    defaultValue="all"
                    options={quickOperationOptions}
                    isReady={isHydrated}
                    skeletonClassName="w-[119px]"
                    className="flex-1 basis-[calc(50%-0.25rem)] sm:flex-none sm:basis-auto"
                />

                <QuickFilter
                    label="Tipo de inmueble"
                    defaultValue="all"
                    options={quickTypeOptions}
                    isReady={isHydrated}
                    skeletonClassName="w-[118px]"
                    className="flex-1 basis-[calc(50%-0.25rem)] sm:flex-none sm:basis-auto"
                />

                <QuickFilter
                    label="Ciudad"
                    defaultValue="all"
                    options={cityOptions}
                    isReady={isHydrated}
                    skeletonClassName="w-[141px]"
                    className="hidden sm:flex"
                />

                <p className="mt-1 flex w-full items-center gap-1.5 text-[10px] text-muted-foreground lg:mt-0 lg:ml-auto lg:w-auto">
                    <Clock3 className="size-3.5 shrink-0" aria-hidden="true" />
                    Los filtros todavía no aplican al inventario
                </p>
            </div>
        </>
    );
}
