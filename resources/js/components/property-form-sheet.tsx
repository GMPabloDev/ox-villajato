import { Form } from '@inertiajs/react';
import { X } from 'lucide-react';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/PropertyController';
import PropertyFormFields, {
    type PropertyFormDefaults,
    PropertySection,
} from '@/components/property-form-fields';
import PropertyPhotoFields, {
    type ExistingPropertyPhoto,
} from '@/components/property-photo-fields';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { useUbigeoCatalog } from '@/hooks/use-ubigeo-catalog';

export type PropertyFormProperty = PropertyFormDefaults & {
    id: number;
    photos: ExistingPropertyPhoto[];
};

export default function PropertyFormSheet({
    open,
    onOpenChange,
    property,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    property?: PropertyFormProperty;
}) {
    const isEditing = property !== undefined;
    const form = isEditing ? update.form(property.id) : store.form();
    const defaults: PropertyFormDefaults = property ?? {};
    const location = useUbigeoCatalog();
    const isLocationReady = location.status === 'ready';

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                showCloseButton={false}
                className="flex w-full flex-col gap-0 border-l border-border bg-background p-0 sm:max-w-[760px]"
            >
                <SheetHeader className="flex-row items-start justify-between gap-4 border-b border-border bg-card px-5 py-5 text-left sm:px-7">
                    <div className="min-w-0">
                        <SheetTitle className="font-display text-xl font-semibold tracking-tight">
                            {isEditing
                                ? 'Editar propiedad'
                                : 'Publicar propiedad'}
                        </SheetTitle>
                        <SheetDescription className="mt-1 text-sm">
                            {isEditing
                                ? 'Actualiza la información y las fotos del anuncio.'
                                : 'Añade la información para crear tu anuncio.'}
                        </SheetDescription>
                    </div>

                    <SheetClose asChild>
                        <Button
                            type="button"
                            variant="secondary"
                            size="icon"
                            className="size-9 shrink-0 rounded-[9px] text-muted-foreground"
                            aria-label="Cerrar"
                        >
                            <X className="size-4" />
                        </Button>
                    </SheetClose>
                </SheetHeader>

                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="flex min-h-0 flex-1 flex-col"
                >
                    {({ processing, errors, progress }) => (
                        <>
                            <div className="min-h-0 flex-1 overflow-y-auto px-4 py-5 sm:px-7">
                                <div className="mx-auto max-w-[850px] space-y-4">
                                    <PropertyFormFields
                                        errors={errors}
                                        defaults={defaults}
                                        location={location}
                                    />

                                    <PropertySection
                                        number="04"
                                        title="Fotos de la propiedad"
                                        description="La primera foto será la portada del anuncio. Puedes cambiar el orden."
                                    >
                                        <PropertyPhotoFields
                                            errors={errors}
                                            existingPhotos={property?.photos}
                                        />
                                    </PropertySection>
                                </div>
                            </div>

                            <SheetFooter className="border-t border-border bg-card px-5 py-4 sm:px-7">
                                <div className="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-xs text-muted-foreground">
                                        {location.status === 'error'
                                            ? 'Reintenta la carga de ubicaciones para guardar.'
                                            : '* Campos obligatorios'}
                                    </p>
                                    <div className="flex w-full gap-2 sm:w-auto">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            className="flex-1 sm:flex-none"
                                            disabled={processing}
                                            onClick={() => onOpenChange(false)}
                                        >
                                            Cancelar
                                        </Button>
                                        <Button
                                            type="submit"
                                            className="flex-1 sm:flex-none"
                                            disabled={
                                                processing || !isLocationReady
                                            }
                                        >
                                            {processing && <Spinner />}
                                            {isEditing
                                                ? 'Guardar cambios'
                                                : 'Publicar propiedad'}
                                        </Button>
                                    </div>
                                </div>

                                {progress && (
                                    <div
                                        className="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                                        role="progressbar"
                                        aria-label="Subiendo fotos"
                                        aria-valuemin={0}
                                        aria-valuemax={100}
                                        aria-valuenow={Math.round(
                                            progress.percentage ?? 0,
                                        )}
                                    >
                                        <div
                                            className="h-full bg-primary transition-all"
                                            style={{
                                                width: `${progress.percentage ?? 0}%`,
                                            }}
                                        />
                                    </div>
                                )}
                            </SheetFooter>
                        </>
                    )}
                </Form>
            </SheetContent>
        </Sheet>
    );
}
