import { Head, Link, useForm } from '@inertiajs/react';
import { Building2, PlusCircle } from 'lucide-react';
import { useState } from 'react';
import {
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

type PanelProperty = {
    id: number;
    title: string;
    price_formatted: string;
    cover_photo_url: string | null;
    status_label: string;
    status_action: 'close' | 'reopen';
    status_action_label: string;
    edit_url: string;
};

export default function PanelPropertiesIndex({
    properties,
    create_url,
}: {
    properties: PanelProperty[];
    create_url: string;
}) {
    const [pendingProperty, setPendingProperty] =
        useState<PanelProperty | null>(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const { submit, processing } = useForm({});

    function requestStatusChange(property: PanelProperty) {
        setPendingProperty(property);
        setIsDialogOpen(true);
    }

    function confirmStatusChange() {
        if (!pendingProperty) {
            return;
        }

        const action =
            pendingProperty.status_action === 'close'
                ? close(pendingProperty.id)
                : reopen(pendingProperty.id);

        submit(action.method, action.url, {
            preserveScroll: true,
            onSuccess: () => setIsDialogOpen(false),
        });
    }

    return (
        <>
            <Head title="Propiedades" />

            <h1 className="sr-only">Propiedades</h1>

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title="Propiedades"
                        description="Todas las propiedades publicadas con su portada, título y precio."
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
                            Aún no hay propiedades publicadas.
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
                                                property.status_action ===
                                                'close'
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
                                        <Button
                                            type="button"
                                            variant={
                                                property.status_action ===
                                                'close'
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            className="w-full"
                                            onClick={() =>
                                                requestStatusChange(property)
                                            }
                                        >
                                            {property.status_action_label}
                                        </Button>
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

            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {pendingProperty?.status_action_label}
                        </DialogTitle>
                        <DialogDescription>
                            {pendingProperty?.status_action === 'close'
                                ? `«${pendingProperty?.title}» dejará de aparecer en el catálogo y su ficha pública ya no estará disponible.`
                                : `«${pendingProperty?.title}» volverá a aparecer en el catálogo y su ficha pública estará disponible.`}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={processing}
                            onClick={() => setIsDialogOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            disabled={processing}
                            onClick={confirmStatusChange}
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
