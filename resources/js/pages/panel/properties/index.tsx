import { Head, Link, useForm } from '@inertiajs/react';
import { Building2, PlusCircle } from 'lucide-react';
import { useState } from 'react';
import {
    archive,
    close,
    panelIndex,
    reopen,
} from '@/actions/App/Http/Controllers/PropertyController';
import Heading from '@/components/heading';
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

type PanelProperty = {
    id: number;
    title: string;
    price_formatted: string;
    cover_photo_url: string | null;
    status: 'available' | 'archived' | 'sold' | 'rented';
    status_label: string;
    status_action: 'close' | 'reopen' | null;
    status_action_label: string | null;
    edit_url: string;
};

type PendingConfirmation = {
    property: PanelProperty;
    action: PendingAction;
};

export default function PanelPropertiesIndex({
    properties,
    create_url,
}: {
    properties: PanelProperty[];
    create_url: string;
}) {
    const [pendingConfirmation, setPendingConfirmation] =
        useState<PendingConfirmation | null>(null);
    const { submit, processing } = useForm({});

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
                    <Button asChild>
                        <Link href={create_url}>
                            <PlusCircle className="size-4" />
                            Publicar propiedad
                        </Link>
                    </Button>
                </div>

                {properties.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-neutral-300 py-20 text-center dark:border-neutral-700">
                        <Building2 className="mb-3 size-10 text-neutral-400" />
                        <p className="font-medium">
                            Aún no hay propiedades para gestionar.
                        </p>
                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Publica la primera para que aparezca en el catálogo.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href={create_url}>Publicar propiedad</Link>
                        </Button>
                    </div>
                ) : (
                    <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {properties.map((property) => (
                            <li
                                key={property.id}
                                className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                            >
                                <div className="aspect-[4/3] overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                                    {property.cover_photo_url ? (
                                        <img
                                            src={property.cover_photo_url}
                                            alt={property.title}
                                            className="size-full object-cover"
                                            loading="lazy"
                                        />
                                    ) : (
                                        <div className="flex size-full items-center justify-center text-neutral-400">
                                            <Building2 className="size-10" />
                                        </div>
                                    )}
                                </div>
                                <div className="space-y-3 p-4">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <h2 className="line-clamp-2 font-semibold">
                                            {property.title}
                                        </h2>
                                        <Badge
                                            variant={
                                                property.status === 'available'
                                                    ? 'secondary'
                                                    : 'outline'
                                            }
                                        >
                                            {property.status_label}
                                        </Badge>
                                    </div>
                                    <p className="text-lg font-semibold">
                                        {property.price_formatted}
                                    </p>
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
                                                    {
                                                        property.status_action_label
                                                    }
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
                                            asChild
                                            variant="outline"
                                            className="w-full"
                                        >
                                            <Link href={property.edit_url}>
                                                Editar
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            </li>
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
