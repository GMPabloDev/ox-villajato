import { Head, useForm } from '@inertiajs/react';
import { Building2, Pencil, PlusCircle } from 'lucide-react';
import { useState } from 'react';
import {
    archive,
    close,
    panelIndex,
    reopen,
} from '@/actions/App/Http/Controllers/PropertyController';
import Heading from '@/components/heading';
import {
    PanelPropertyCard,
    type PanelPropertyCardData,
} from '@/components/panel-property-card';
import PropertyFormSheet, {
    type PropertyFormProperty,
} from '@/components/property-form-sheet';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

type PendingAction = 'archive' | 'close' | 'reopen';

type PanelProperty = PanelPropertyCardData &
    PropertyFormProperty & {
        status: 'available' | 'archived' | 'sold' | 'rented';
        status_label: string;
        status_action: 'close' | 'reopen' | null;
        status_action_label: string | null;
    };

type PendingConfirmation = {
    property: PanelProperty;
    action: PendingAction;
};

type OpenSheet = { mode: 'create' } | { mode: 'edit'; property: PanelProperty };

export default function PanelPropertiesIndex({
    properties,
}: {
    properties: PanelProperty[];
}) {
    const [pendingConfirmation, setPendingConfirmation] =
        useState<PendingConfirmation | null>(null);
    const [openSheet, setOpenSheet] = useState<OpenSheet | null>(null);
    const [isSheetOpen, setIsSheetOpen] = useState(false);
    const [sheetKey, setSheetKey] = useState(0);
    const { submit, processing } = useForm({});

    function openCreateSheet() {
        setSheetKey((key) => key + 1);
        setOpenSheet({ mode: 'create' });
        setIsSheetOpen(true);
    }

    function openEditSheet(property: PanelProperty) {
        setSheetKey((key) => key + 1);
        setOpenSheet({ mode: 'edit', property });
        setIsSheetOpen(true);
    }

    function requestStatusChange(property: PanelProperty) {
        if (property.status_action === null) {
            return;
        }

        setPendingConfirmation({
            property,
            action: property.status_action,
        });
    }

    function requestArchive(property: PanelProperty) {
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

    const pendingProperty = pendingConfirmation?.property;
    const pendingAction = pendingConfirmation?.action;

    return (
        <>
            <Head title="Propiedades" />

            <h1 className="sr-only">Propiedades</h1>

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title="Propiedades"
                        description="Propiedades que gestionas, con su portada, título y precio."
                    />
                    <Button type="button" onClick={openCreateSheet}>
                        <PlusCircle className="size-4" />
                        Publicar propiedad
                    </Button>
                </div>

                {properties.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-border py-20 text-center">
                        <Building2 className="mb-3 size-10 text-muted-foreground" />
                        <p className="font-medium">
                            Aún no hay propiedades para gestionar.
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Publica la primera para que aparezca en el catálogo.
                        </p>
                        <Button
                            type="button"
                            className="mt-4"
                            onClick={openCreateSheet}
                        >
                            Publicar propiedad
                        </Button>
                    </div>
                ) : (
                    <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {properties.map((property) => (
                            <PanelPropertyCard
                                key={property.id}
                                property={property}
                                statusBadge={
                                    <Badge
                                        variant={
                                            property.status === 'available'
                                                ? 'secondary'
                                                : 'outline'
                                        }
                                    >
                                        {property.status_label}
                                    </Badge>
                                }
                            >
                                <div className="grid gap-2">
                                    {property.status_action !== null &&
                                        property.status_action_label !==
                                            null && (
                                            <Button
                                                type="button"
                                                variant={
                                                    property.status ===
                                                    'available'
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                className="w-full"
                                                onClick={() =>
                                                    requestStatusChange(
                                                        property,
                                                    )
                                                }
                                            >
                                                {property.status_action_label}
                                            </Button>
                                        )}
                                    {property.status === 'available' && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="w-full"
                                            onClick={() =>
                                                requestArchive(property)
                                            }
                                        >
                                            Archivar
                                        </Button>
                                    )}
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="w-full"
                                        onClick={() => openEditSheet(property)}
                                    >
                                        <Pencil className="size-4" />
                                        Editar
                                    </Button>
                                </div>
                            </PanelPropertyCard>
                        ))}
                    </ul>
                )}
            </div>

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
