import { Head, Link, router, useForm } from '@inertiajs/react';
import { Building2, Plus, SearchX, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    archive,
    close,
    panelIndex,
    reopen,
} from '@/actions/App/Http/Controllers/PropertyController';
import { PanelPropertyDetail } from '@/components/panel-property-detail';
import {
    PanelPropertyFilters,
    type PanelPropertyCounts,
} from '@/components/panel-property-filters';
import {
    PanelPropertyListItem,
    type PanelPropertyListItemData,
} from '@/components/panel-property-list-item';
import PropertyFormSheet, {
    type PropertyFormProperty,
} from '@/components/property-form-sheet';
import type { PropertyStatusValue } from '@/components/property-status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import {
    hasActivePanelFilters,
    panelFiltersToQuery,
    type PanelPropertyFiltersValue,
    type PanelPropertyZonesByCity,
} from '@/lib/panel-property-filters';
import {
    type PropertyCurrencyValue,
    type PropertyOperationValue,
    type PropertyTypeValue,
} from '@/lib/property-options';
import { cn } from '@/lib/utils';

type PendingAction = 'archive' | 'close' | 'reopen';

type PanelProperty = {
    id: number;
    title: string;
    price_formatted: string;
    cover_photo_url: string | null;
} & PropertyFormProperty & {
        description: string;
        operation: PropertyOperationValue;
        operation_label: string;
        type: PropertyTypeValue;
        type_label: string;
        currency: PropertyCurrencyValue;
        city: string;
        zone: string;
        bedrooms: number;
        bathrooms: number;
        area: string;
        status: PropertyStatusValue;
        status_label: string;
        status_action: 'close' | 'reopen' | null;
        status_action_label: string | null;
    };

type PendingConfirmation = {
    property: PanelProperty;
    action: PendingAction;
};

type OpenSheet = { mode: 'create' } | { mode: 'edit'; property: PanelProperty };

type PaginationLink = {
    active: boolean;
    label: string;
    url: string | null;
};

type PaginatedPanelProperties = {
    data: PanelProperty[];
    current_page: number;
    last_page: number;
    links: PaginationLink[];
    total: number;
    from: number | null;
    to: number | null;
};

function pluralize(count: number, singular: string, plural: string): string {
    return `${count} ${count === 1 ? singular : plural}`;
}

export default function PanelPropertiesIndex({
    properties,
    filters,
    counts,
    cities,
    zonesByCity,
    filterErrors,
}: {
    properties: PaginatedPanelProperties;
    filters: PanelPropertyFiltersValue;
    counts: PanelPropertyCounts;
    cities: string[];
    zonesByCity: PanelPropertyZonesByCity;
    filterErrors?: { price?: string };
}) {
    const propertyList = properties.data;
    const [selectedId, setSelectedId] = useState<number | null>(
        propertyList[0]?.id ?? null,
    );
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [pendingConfirmation, setPendingConfirmation] =
        useState<PendingConfirmation | null>(null);
    const [openSheet, setOpenSheet] = useState<OpenSheet | null>(null);
    const [isSheetOpen, setIsSheetOpen] = useState(false);
    const [sheetKey, setSheetKey] = useState(0);
    const [filtersKey, setFiltersKey] = useState(0);
    const { submit, processing } = useForm({});

    const selectedProperty =
        propertyList.find((property) => property.id === selectedId) ??
        propertyList[0] ??
        null;

    useEffect(() => {
        setSelectedId(selectedProperty?.id ?? null);
    }, [selectedProperty?.id]);

    const hasActiveFilters = hasActivePanelFilters(filters);
    const hasResults = propertyList.length > 0;
    const isInventoryEmpty = counts.all === 0 && !hasActiveFilters;
    const resultsLabel = hasActiveFilters
        ? pluralize(properties.total, 'coincidencia', 'coincidencias')
        : `${pluralize(properties.total, 'propiedad', 'propiedades')} en tu inventario`;
    const footerLabel = hasResults
        ? `Mostrando ${properties.from ?? 0} a ${properties.to ?? 0} de ${pluralize(properties.total, 'propiedad', 'propiedades')}`
        : 'Sin propiedades para mostrar';

    function applyFilters(next: PanelPropertyFiltersValue) {
        router.get(panelIndex.url(), panelFiltersToQuery(next), {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function clearFilters() {
        setFiltersKey((key) => key + 1);
        router.get(
            panelIndex.url(),
            {},
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    }

    function selectProperty(property: PanelPropertyListItemData) {
        setSelectedId(property.id);

        if (window.matchMedia('(max-width: 1023px)').matches) {
            setIsDetailOpen(true);
        }
    }

    function openCreateSheet() {
        setSheetKey((key) => key + 1);
        setOpenSheet({ mode: 'create' });
        setIsSheetOpen(true);
    }

    function openEditSheet(property: PanelProperty) {
        setIsDetailOpen(false);
        setSheetKey((key) => key + 1);
        setOpenSheet({ mode: 'edit', property });
        setIsSheetOpen(true);
    }

    function requestStatusChange(property: PanelProperty) {
        if (property.status_action === null) {
            return;
        }

        setIsDetailOpen(false);
        setPendingConfirmation({
            property,
            action: property.status_action,
        });
    }

    function requestArchive(property: PanelProperty) {
        setIsDetailOpen(false);
        setPendingConfirmation({ property, action: 'archive' });
    }

    function confirmPendingAction() {
        if (pendingConfirmation === null) {
            return;
        }

        const { action: pendingAction, property } = pendingConfirmation;
        const action =
            pendingAction === 'archive'
                ? archive(property.id)
                : pendingAction === 'close'
                  ? close(property.id)
                  : reopen(property.id);

        submit(action.method, action.url, {
            preserveScroll: true,
            onSuccess: () => setPendingConfirmation(null),
        });
    }

    function renderDetail(property: PanelProperty) {
        return (
            <PanelPropertyDetail
                property={property}
                onEdit={() => openEditSheet(property)}
                onStatusChange={() => requestStatusChange(property)}
                onArchive={() => requestArchive(property)}
            />
        );
    }

    const pendingProperty = pendingConfirmation?.property;
    const pendingAction = pendingConfirmation?.action;

    return (
        <>
            <Head title="Propiedades" />

            <div className="mx-auto w-full max-w-[1660px] space-y-7 p-4 pb-10 md:p-6 lg:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-[10px] font-semibold tracking-[0.18em] text-muted-foreground">
                            TU CARTERA INMOBILIARIA
                        </p>
                        <h1 className="mt-2.5 font-display text-[31px] leading-tight font-extrabold tracking-[-1.4px] sm:text-[34px]">
                            Propiedades
                            <span className="text-[#bf7959] dark:text-brand-warm">
                                .
                            </span>
                        </h1>
                        <p className="mt-2.5 text-[13px] leading-[1.6] text-muted-foreground">
                            Todo tu inventario, con cada detalle en su lugar.
                        </p>
                    </div>

                    <Button
                        type="button"
                        onClick={openCreateSheet}
                        className="h-11 w-full gap-2 self-start rounded-[10px] px-4 text-xs font-semibold sm:h-[46px] sm:w-auto sm:self-center sm:px-[18px]"
                    >
                        <Plus className="size-4" aria-hidden="true" />
                        Publicar propiedad
                    </Button>
                </div>

                <section
                    aria-label="Inventario de propiedades"
                    className="overflow-hidden rounded-[17px] border border-border bg-card shadow-sm"
                >
                    <PanelPropertyFilters
                        key={filtersKey}
                        filters={filters}
                        counts={counts}
                        cities={cities}
                        zonesByCity={zonesByCity}
                        priceRangeError={filterErrors?.price ?? null}
                        onApply={applyFilters}
                    />

                    <div className="flex flex-wrap items-center justify-between gap-2 border-t border-border bg-muted/40 px-4 py-3 text-xs md:px-6">
                        <p aria-live="polite" className="text-muted-foreground">
                            <strong className="font-semibold text-foreground">
                                {resultsLabel}
                            </strong>
                        </p>
                        {hasResults && (
                            <span className="hidden text-[11px] text-muted-foreground sm:inline">
                                Elige un inmueble para abrir su vista previa
                            </span>
                        )}
                    </div>

                    {hasResults ? (
                        <div className="grid lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
                            <ul
                                aria-label="Propiedades"
                                className="min-w-0 lg:border-r lg:border-border [&>li:last-child>button]:border-b-0"
                            >
                                {propertyList.map((property) => (
                                    <PanelPropertyListItem
                                        key={property.id}
                                        property={property}
                                        isSelected={
                                            property.id === selectedProperty?.id
                                        }
                                        onSelect={selectProperty}
                                    />
                                ))}
                            </ul>

                            {selectedProperty !== null && (
                                <section
                                    aria-label="Detalle de la propiedad seleccionada"
                                    className="hidden min-w-0 p-5 lg:sticky lg:top-4 lg:block lg:max-h-[calc(100dvh-2rem)] lg:self-start lg:overflow-y-auto xl:p-6"
                                >
                                    <div aria-live="polite">
                                        {renderDetail(selectedProperty)}
                                    </div>
                                </section>
                            )}
                        </div>
                    ) : isInventoryEmpty ? (
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center md:py-20">
                            <span className="grid size-14 place-items-center rounded-2xl bg-muted text-primary">
                                <Building2
                                    className="size-6"
                                    aria-hidden="true"
                                />
                            </span>
                            <h2 className="mt-5 font-display text-[19px] font-extrabold tracking-[-0.5px]">
                                Aún no hay propiedades para gestionar
                            </h2>
                            <p className="mt-2 max-w-sm text-xs leading-[1.8] text-muted-foreground">
                                Publica la primera para que aparezca en el
                                catálogo.
                            </p>
                            <Button
                                type="button"
                                onClick={openCreateSheet}
                                className="mt-6 h-10 gap-2 rounded-[10px] px-4 text-xs font-semibold"
                            >
                                <Plus className="size-4" aria-hidden="true" />
                                Publicar propiedad
                            </Button>
                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center md:py-20">
                            <span className="grid size-14 place-items-center rounded-2xl bg-muted text-primary">
                                <SearchX
                                    className="size-6"
                                    aria-hidden="true"
                                />
                            </span>
                            <h2 className="mt-5 font-display text-[19px] font-extrabold tracking-[-0.5px]">
                                No hay propiedades que coincidan
                            </h2>
                            <p className="mt-2 max-w-sm text-xs leading-[1.8] text-muted-foreground">
                                Prueba con otros criterios o limpia la búsqueda
                                y los filtros para volver a ver todo tu
                                inventario.
                            </p>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={clearFilters}
                                className="mt-6 h-10 gap-2 rounded-[10px] border-input px-4 text-xs font-semibold"
                            >
                                Limpiar búsqueda y filtros
                            </Button>
                        </div>
                    )}

                    <footer className="flex flex-col gap-3 border-t border-border bg-muted/30 px-4 py-3 text-[11px] text-muted-foreground md:flex-row md:items-center md:justify-between md:px-6">
                        <span>{footerLabel}</span>

                        {properties.last_page > 1 && (
                            <nav
                                aria-label="Paginación de propiedades"
                                className="flex flex-wrap items-center gap-1.5"
                            >
                                {properties.links.map((link, index) => {
                                    const label =
                                        index === 0
                                            ? 'Anterior'
                                            : index ===
                                                properties.links.length - 1
                                              ? 'Siguiente'
                                              : link.label;

                                    if (link.url === null) {
                                        return (
                                            <span
                                                key={index}
                                                aria-disabled="true"
                                                className="grid min-h-7 min-w-7 cursor-not-allowed place-items-center rounded-[6px] border border-border bg-card px-2 text-[10px] text-muted-foreground/50"
                                            >
                                                {label}
                                            </span>
                                        );
                                    }

                                    return (
                                        <Link
                                            key={index}
                                            href={link.url}
                                            aria-current={
                                                link.active ? 'page' : undefined
                                            }
                                            className={cn(
                                                'grid min-h-7 min-w-7 place-items-center rounded-[6px] border px-2 text-[10px] transition-colors',
                                                link.active
                                                    ? 'border-primary bg-secondary font-semibold text-secondary-foreground'
                                                    : 'border-border bg-card text-muted-foreground hover:bg-muted hover:text-foreground',
                                            )}
                                        >
                                            {label}
                                        </Link>
                                    );
                                })}
                            </nav>
                        )}

                        <span>
                            Precios en su moneda original. Sin conversión.
                        </span>
                    </footer>
                </section>
            </div>

            <Sheet open={isDetailOpen} onOpenChange={setIsDetailOpen}>
                <SheetContent
                    side="right"
                    showCloseButton={false}
                    className="flex w-full flex-col gap-0 p-0 sm:max-w-[520px]"
                >
                    <SheetHeader className="flex-row items-start justify-between gap-3 border-b border-border px-5 py-4 text-left">
                        <div className="min-w-0">
                            <p className="text-[10px] font-semibold tracking-[0.16em] text-muted-foreground">
                                VISTA PREVIA
                            </p>
                            <SheetTitle className="mt-1.5 font-display text-lg font-bold tracking-tight">
                                Detalle de la propiedad
                            </SheetTitle>
                            <SheetDescription className="sr-only">
                                Información y acciones de la propiedad
                                seleccionada.
                            </SheetDescription>
                        </div>

                        <SheetClose asChild>
                            <Button
                                type="button"
                                variant="secondary"
                                size="icon"
                                className="size-9 shrink-0 rounded-[9px] text-muted-foreground"
                                aria-label="Cerrar detalle"
                            >
                                <X className="size-4" aria-hidden="true" />
                            </Button>
                        </SheetClose>
                    </SheetHeader>

                    <div className="min-h-0 flex-1 overflow-y-auto p-5">
                        {selectedProperty !== null &&
                            renderDetail(selectedProperty)}
                    </div>
                </SheetContent>
            </Sheet>

            <Dialog
                open={pendingConfirmation !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingConfirmation(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {pendingAction === 'archive'
                                ? 'Archivar propiedad'
                                : pendingProperty?.status_action_label}
                        </DialogTitle>
                        <DialogDescription>
                            {pendingAction === 'archive'
                                ? `«${pendingProperty?.title}» dejará de aparecer en el listado normal y en el catálogo público. No se marcará como vendida ni alquilada.`
                                : pendingProperty?.status_action === 'close'
                                  ? `«${pendingProperty?.title}» dejará de aparecer en el catálogo y su ficha pública ya no estará disponible.`
                                  : pendingProperty
                                    ? `«${pendingProperty.title}» volverá a aparecer en el catálogo y su ficha pública estará disponible.`
                                    : null}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={processing}
                            onClick={() => setPendingConfirmation(null)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            disabled={processing}
                            onClick={confirmPendingAction}
                        >
                            {processing && <Spinner />}
                            Confirmar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {openSheet !== null && (
                <PropertyFormSheet
                    key={sheetKey}
                    open={isSheetOpen}
                    onOpenChange={setIsSheetOpen}
                    property={
                        openSheet.mode === 'edit'
                            ? openSheet.property
                            : undefined
                    }
                />
            )}
        </>
    );
}

PanelPropertiesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Propiedades',
            href: panelIndex(),
        },
    ],
};
