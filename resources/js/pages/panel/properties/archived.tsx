import { Head, useForm } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { useState } from 'react';
import {
    archivedIndex,
    republish,
} from '@/actions/App/Http/Controllers/PropertyController';
import Heading from '@/components/heading';
import {
    PanelPropertyCard,
    type PanelPropertyCardData,
} from '@/components/panel-property-card';
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

type ArchivedProperty = PanelPropertyCardData & {
    city: string;
    zone: string;
};

export default function ArchivedProperties({
    properties,
}: {
    properties: ArchivedProperty[];
}) {
    const [pendingProperty, setPendingProperty] =
        useState<ArchivedProperty | null>(null);
    const { submit, processing } = useForm({});

    function confirmRepublish() {
        if (pendingProperty === null) {
            return;
        }

        const action = republish(pendingProperty.id);

        submit(action.method, action.url, {
            preserveScroll: true,
            onSuccess: () => setPendingProperty(null),
        });
    }

    return (
        <>
            <Head title="Propiedades archivadas" />

            <h1 className="sr-only">Propiedades archivadas</h1>

            <div className="space-y-6 p-4">
                <Heading
                    title="Propiedades archivadas"
                    description="Propiedades que ya no están publicadas en el catálogo."
                />

                {properties.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-neutral-300 py-20 text-center dark:border-neutral-700">
                        <Building2 className="mb-3 size-10 text-neutral-400" />
                        <p className="font-medium">
                            No hay propiedades archivadas.
                        </p>
                    </div>
                ) : (
                    <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {properties.map((property) => (
                            <PanelPropertyCard
                                key={property.id}
                                property={property}
                                contentSpacing="compact"
                                metadata={
                                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                        {property.city} · {property.zone}
                                    </p>
                                }
                            >
                                <Button
                                    type="button"
                                    className="w-full"
                                    onClick={() => setPendingProperty(property)}
                                >
                                    Republicar
                                </Button>
                            </PanelPropertyCard>
                        ))}
                    </ul>
                )}
            </div>

            <Dialog
                open={pendingProperty !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingProperty(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Republicar propiedad</DialogTitle>
                        <DialogDescription>
                            {pendingProperty
                                ? `«${pendingProperty.title}» volverá a aparecer en el catálogo y su ficha pública y formulario de consulta estarán disponibles.`
                                : null}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={processing}
                            onClick={() => setPendingProperty(null)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            disabled={processing || pendingProperty === null}
                            onClick={confirmRepublish}
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

ArchivedProperties.layout = {
    breadcrumbs: [
        {
            title: 'Propiedades archivadas',
            href: archivedIndex(),
        },
    ],
};
