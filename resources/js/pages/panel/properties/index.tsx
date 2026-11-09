import { Head, useForm } from '@inertiajs/react';
import { Building2, Plus, X } from 'lucide-react';
import { useState } from 'react';
import {
    archive,
    close,
    panelIndex,
    reopen,
} from '@/actions/App/Http/Controllers/PropertyController';
import { PanelPropertyDetail } from '@/components/panel-property-detail';
import { PanelPropertyFilters } from '@/components/panel-property-filters';
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
    type PropertyCurrencyValue,
    type PropertyOperationValue,
    type PropertyTypeValue,
} from '@/lib/property-options';

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

function compareText(first: string, second: string): number {
    return first.localeCompare(second, 'es', { sensitivity: 'base' });
}

export default function PanelPropertiesIndex({
    properties,
}: {
    properties: PanelProperty[];
}) {
    const [selectedId, setSelectedId] = useState<number | null>(
        properties[0]?.id ?? null,
    );
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [pendingConfirmation, setPendingConfirmation] =
        useState<PendingConfirmation | null>(null);
    const [openSheet, setOpenSheet] = useState<OpenSheet | null>(null);
    const [isSheetOpen, setIsSheetOpen] = useState(false);
    const [sheetKey, setSheetKey] = useState(0);
    const { submit, processing } = useForm({});

    const selectedProperty =
        properties.find((property) => property.id === selectedId) ??
        properties[0] ??
        null;

    const counts = {
        all: properties.length,
        available: properties.filter(
            (property) => property.status === 'available',
        ).length,
        sold: properties.filter((property) => property.status === 'sold')
            .length,
        rented: properties.filter((property) => property.status === 'rented')
            .length,
    };

    const cities = [
        ...new Set(properties.map((property) => property.city)),
    ].sort(compareText);
    const zones = [
        ...new Set(properties.map((property) => property.zone)),
    ].sort(compareText);

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
    const hasProperties = properties.length > 0;
    const resultsLabel = `${counts.all} ${
        counts.all === 1 ? 'propiedad' : 'propiedades'
    } en tu inventario`;

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
                        counts={counts}
                        cities={cities}
                        zones={zones}
                    />

                    <div className="flex flex-wrap items-center justify-between gap-2 border-t border-border bg-muted/40 px-4 py-3 text-xs md:px-6">
                        <p aria-live="polite" className="text-muted-foreground">
                            <strong className="font-semibold text-foreground">
                                {resultsLabel}
                            </strong>
                        </p>
                        {selectedProperty !== null && (
                            <span className="hidden text-[11px] text-muted-foreground sm:inline">
                                Elige un inmueble para abrir su vista previa
                            </span>
                        )}
                    </div>

                    {hasProperties ? (
                        <div className="grid lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
                            <ul
                                aria-label="Propiedades"
                                className="min-w-0 lg:border-r lg:border-border [&>li:last-child>button]:border-b-0"
                            >
                                {properties.map((property) => (
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
                    ) : (
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
                    )}

                    <footer className="flex flex-col gap-1 border-t border-border bg-muted/30 px-4 py-3 text-[11px] text-muted-foreground md:flex-row md:items-center md:justify-between md:px-6">
                        <span>
                            Mostrando {counts.all}{' '}
                            {counts.all === 1 ? 'propiedad' : 'propiedades'}
                        </span>
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
