import { Head, useForm } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { useState } from 'react';
import {
    archivedIndex,
    destroy as deleteArchivedProperty,
    republish,
} from '@/actions/App/Http/Controllers/PropertyController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {
    PanelPropertyCard,
    type PanelPropertyCardData,
} from '@/components/panel-property-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    canDelete,
}: {
    properties: ArchivedProperty[];
    canDelete: boolean;
}) {
    const [pendingProperty, setPendingProperty] =
        useState<ArchivedProperty | null>(null);
    const [pendingDeletion, setPendingDeletion] =
        useState<ArchivedProperty | null>(null);
    const { submit, processing } = useForm({});
    const {
        clearErrors: clearDeletionErrors,
        data: deletionData,
        errors: deletionErrors,
        processing: deletionProcessing,
        reset: resetDeletionForm,
        setData: setDeletionData,
        submit: submitDeletion,
    } = useForm({ title_confirmation: '' });

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

    function requestDeletion(property: ArchivedProperty) {
        clearDeletionErrors();
        resetDeletionForm();
        setPendingDeletion(property);
    }

    function closeDeletionDialog() {
        setPendingDeletion(null);
        resetDeletionForm();
        clearDeletionErrors();
    }

    function confirmDeletion() {
        if (pendingDeletion === null) {
            return;
        }

        const action = deleteArchivedProperty(pendingDeletion.id);

        submitDeletion(action.method, action.url, {
            preserveScroll: true,
            onSuccess: closeDeletionDialog,
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
                    <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-border py-20 text-center ">
                        <Building2 className="mb-3 size-10 text-muted-foreground" />
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
                                    <p className="text-sm text-muted-foreground">
                                        {property.city} · {property.zone}
                                    </p>
                                }
                            >
                                <div className="grid gap-2">
                                    <Button
                                        type="button"
                                        className="w-full"
                                        onClick={() =>
                                            setPendingProperty(property)
                                        }
                                    >
                                        Republicar
                                    </Button>
                                    {canDelete && (
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            className="w-full"
                                            onClick={() =>
                                                requestDeletion(property)
                                            }
                                        >
                                            Eliminar
                                        </Button>
                                    )}
                                </div>
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

            <Dialog
                open={pendingDeletion !== null}
                onOpenChange={(open) => {
                    if (!open && !deletionProcessing) {
                        closeDeletionDialog();
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar propiedad</DialogTitle>
                        <DialogDescription>
                            {pendingDeletion
                                ? `Escribe exactamente «${pendingDeletion.title}» para confirmar. La propiedad y sus fotos se eliminarán; las consultas recibidas se conservarán.`
                                : null}
                        </DialogDescription>
                    </DialogHeader>
                    <form
                        className="grid gap-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            confirmDeletion();
                        }}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="title_confirmation">
                                Título de la propiedad
                            </Label>
                            <Input
                                id="title_confirmation"
                                name="title_confirmation"
                                autoComplete="off"
                                value={deletionData.title_confirmation}
                                onChange={(event) =>
                                    setDeletionData(
                                        'title_confirmation',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={Boolean(
                                    deletionErrors.title_confirmation,
                                )}
                                aria-describedby={
                                    deletionErrors.title_confirmation
                                        ? 'title_confirmation-error'
                                        : undefined
                                }
                            />
                            {deletionErrors.title_confirmation && (
                                <InputError
                                    id="title_confirmation-error"
                                    message={deletionErrors.title_confirmation}
                                />
                            )}
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                disabled={deletionProcessing}
                                onClick={closeDeletionDialog}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={
                                    deletionProcessing ||
                                    pendingDeletion === null
                                }
                            >
                                {deletionProcessing && <Spinner />}
                                Eliminar definitivamente
                            </Button>
                        </DialogFooter>
                    </form>
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
