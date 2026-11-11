import { Form, Head, Link } from '@inertiajs/react';
import { Bath, BedDouble, Maximize, type LucideIcon } from 'lucide-react';
import { store } from '@/actions/App/Http/Controllers/PropertyInquiryController';
import InputError from '@/components/input-error';
import PropertyPhotoGallery, {
    type PropertyPhoto,
} from '@/components/property-photo-gallery';
import PublicContainer from '@/components/public-container';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { PropertyOperationValue } from '@/lib/property-options';
import { locationLabel } from '@/lib/ubigeo';
import { cn } from '@/lib/utils';
import { home } from '@/routes';

type Property = {
    id: number;
    title: string;
    description: string;
    operation: PropertyOperationValue;
    type_label: string;
    price_formatted: string;
    district: string;
    province: string;
    department: string;
    bedrooms: number;
    bathrooms: number;
    area: string;
    photos: PropertyPhoto[];
};

const fieldClassName =
    'h-auto rounded-[7px] border-border bg-card px-3 py-3 text-[13px] shadow-none md:text-[13px]';

const defaultMessage =
    'Hola, me interesa recibir más información sobre esta propiedad.';

function PropertyFeature({
    icon: Icon,
    value,
    label,
}: {
    icon: LucideIcon;
    value: string;
    label: string;
}) {
    return (
        <div className="flex items-center gap-x-2.5 md:gap-x-[15px]">
            <span
                aria-hidden="true"
                className="grid size-[46px] flex-none place-items-center rounded-xl bg-secondary text-primary md:size-[52px] md:rounded-[14px]"
            >
                <Icon
                    className="size-[23px] md:size-[25px]"
                    strokeWidth={1.6}
                />
            </span>
            <div>
                <strong className="block font-display text-[15px] font-bold md:text-[17px]">
                    {value}
                </strong>
                <span className="mt-1 block text-xs text-muted-foreground md:mt-[5px]">
                    {label}
                </span>
            </div>
        </div>
    );
}

export default function PropertyShow({ property }: { property: Property }) {
    const operationNoun = property.operation === 'rent' ? 'alquiler' : 'venta';
    const area = Number(property.area).toLocaleString('es-PE', {
        maximumFractionDigits: 2,
    });

    return (
        <>
            <Head title={property.title} />

            <PublicContainer className="pt-2 pb-12 md:pt-2.5 lg:pb-14">
                <nav aria-label="Migas de pan" className="pb-[23px] md:pb-7">
                    <Link
                        href={home()}
                        className="inline-flex items-center gap-[10px] text-xs font-semibold text-muted-foreground transition-colors hover:text-brand-primary"
                    >
                        <span aria-hidden="true">←</span>
                        Volver a propiedades
                    </Link>
                </nav>

                <section className="vj-detail-enter mb-[22px] md:mb-7">
                    <h1 className="font-display text-[clamp(1.875rem,3.2vw,2.875rem)] leading-[1.15] font-bold tracking-[-0.045em]">
                        {property.title}
                    </h1>
                    <div className="mt-2.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[13px] text-muted-foreground md:mt-3">
                        <span>{locationLabel(property)}</span>
                        <span
                            aria-hidden="true"
                            className="size-1 rounded-full bg-[#a8bbb4]"
                        />
                        <span className="font-bold text-brand-primary">
                            {property.type_label} en {operationNoun}
                        </span>
                    </div>
                </section>

                <PropertyPhotoGallery
                    photos={property.photos}
                    title={property.title}
                />

                <div className="grid gap-8 pt-[30px] lg:grid-cols-[minmax(0,1fr)_390px] lg:items-start lg:gap-20 lg:pt-[50px]">
                    <div className="min-w-0">
                        <div className="vj-detail-enter grid grid-cols-2 gap-x-3 gap-y-5 border-b border-border pb-[22px] md:grid-cols-3 md:gap-6 md:pb-[30px]">
                            <PropertyFeature
                                icon={Maximize}
                                value={`${area} m²`}
                                label="Área total"
                            />
                            <PropertyFeature
                                icon={BedDouble}
                                value={String(property.bedrooms)}
                                label={
                                    property.bedrooms === 1
                                        ? 'Dormitorio'
                                        : 'Dormitorios'
                                }
                            />
                            <PropertyFeature
                                icon={Bath}
                                value={String(property.bathrooms)}
                                label={
                                    property.bathrooms === 1 ? 'Baño' : 'Baños'
                                }
                            />
                        </div>

                        <section className="border-b border-border py-[30px] md:py-[38px]">
                            <h2 className="font-display text-[25px] font-bold tracking-[-0.03em]">
                                Descripción
                            </h2>
                            <p className="mt-[18px] max-w-[720px] text-[15px] leading-[1.9] whitespace-pre-line text-muted-foreground">
                                {property.description}
                            </p>
                        </section>

                        <section className="border-b border-border py-[30px] md:py-[38px]">
                            <h2 className="font-display text-[25px] font-bold tracking-[-0.03em]">
                                Ubicación
                            </h2>
                            <p className="mt-[18px] max-w-[720px] text-[15px] leading-[1.9] text-muted-foreground">
                                {locationLabel(property)}
                            </p>
                        </section>
                    </div>

                    <aside
                        id="consulta"
                        className="scroll-mt-6 rounded-xl border border-border bg-card p-7 shadow-[0_12px_35px_rgba(25,47,48,0.035)] lg:sticky lg:top-5"
                    >
                        <p className="text-xs text-muted-foreground">
                            Precio de {operationNoun}
                        </p>
                        <p className="mt-2 font-display text-[27px] leading-none font-bold">
                            {property.price_formatted}
                        </p>

                        <hr className="my-[22px] border-t border-border" />

                        <h2 className="font-display text-[22px] font-bold tracking-[-0.03em]">
                            ¿Te interesa esta propiedad?
                        </h2>
                        <p className="mt-2 mb-5 text-xs leading-[1.6] text-muted-foreground">
                            Cuéntanos un poco sobre ti y nos pondremos en
                            contacto contigo.
                        </p>

                        <Form
                            {...store.form(property.id)}
                            options={{ preserveScroll: true }}
                            resetOnSuccess
                            noValidate
                        >
                            {({ processing, errors }) => (
                                <div className="space-y-3.5">
                                    <div>
                                        <Label
                                            htmlFor="inquiry-name"
                                            className="mb-[7px] block text-xs font-semibold text-foreground"
                                        >
                                            Nombre completo
                                        </Label>
                                        <Input
                                            id="inquiry-name"
                                            name="name"
                                            maxLength={255}
                                            autoComplete="name"
                                            placeholder="Tu nombre"
                                            className={fieldClassName}
                                        />
                                        <InputError
                                            message={errors.name}
                                            className="mt-1.5 text-xs"
                                        />
                                    </div>

                                    <div>
                                        <Label
                                            htmlFor="inquiry-email"
                                            className="mb-[7px] block text-xs font-semibold text-foreground"
                                        >
                                            Correo electrónico
                                        </Label>
                                        <Input
                                            id="inquiry-email"
                                            name="email"
                                            type="email"
                                            maxLength={255}
                                            autoComplete="email"
                                            placeholder="tu@correo.com"
                                            className={fieldClassName}
                                        />
                                        <InputError
                                            message={errors.email}
                                            className="mt-1.5 text-xs"
                                        />
                                    </div>

                                    <div>
                                        <Label
                                            htmlFor="inquiry-phone"
                                            className="mb-[7px] block text-xs font-semibold text-foreground"
                                        >
                                            Teléfono{' '}
                                            <span className="font-normal text-muted-foreground">
                                                (opcional)
                                            </span>
                                        </Label>
                                        <Input
                                            id="inquiry-phone"
                                            name="phone"
                                            type="tel"
                                            maxLength={20}
                                            autoComplete="tel"
                                            placeholder="Tu número de contacto"
                                            className={fieldClassName}
                                        />
                                        <InputError
                                            message={errors.phone}
                                            className="mt-1.5 text-xs"
                                        />
                                    </div>

                                    <div>
                                        <Label
                                            htmlFor="inquiry-message"
                                            className="mb-[7px] block text-xs font-semibold text-foreground"
                                        >
                                            Mensaje
                                        </Label>
                                        <Textarea
                                            id="inquiry-message"
                                            name="message"
                                            rows={3}
                                            defaultValue={defaultMessage}
                                            className={cn(
                                                fieldClassName,
                                                'min-h-[80px] resize-y',
                                            )}
                                        />
                                        <InputError
                                            message={errors.message}
                                            className="mt-1.5 text-xs"
                                        />
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        aria-busy={processing}
                                        className="mt-[18px] flex w-full items-center justify-between gap-4 rounded-[8px] bg-primary px-4.5 py-[15px] text-left text-[13px] font-bold text-primary-foreground transition-colors duration-200 hover:bg-brand-primary-hover disabled:cursor-not-allowed disabled:opacity-80 motion-reduce:transition-none"
                                    >
                                        <span>
                                            {processing
                                                ? 'Enviando consulta…'
                                                : 'Enviar consulta'}
                                        </span>
                                        <span
                                            aria-hidden="true"
                                            className="text-base"
                                        >
                                            ↗
                                        </span>
                                    </button>

                                    <p className="text-[11px] leading-[1.6] text-[#718984] dark:text-muted-foreground">
                                        Usaremos tus datos solo para contactarte
                                        por esta propiedad.
                                    </p>
                                </div>
                            )}
                        </Form>
                    </aside>
                </div>
            </PublicContainer>

            <div className="sticky bottom-0 z-30 flex items-center justify-between gap-4 border-t border-border bg-card px-5 py-3 shadow-[0_-6px_24px_rgba(25,47,48,0.07)] lg:hidden">
                <strong className="font-display text-[17px] font-bold">
                    {property.price_formatted}
                </strong>
                <a
                    href="#consulta"
                    className="rounded-[7px] bg-primary px-4 py-[13px] text-xs font-bold text-primary-foreground transition-colors duration-200 hover:bg-brand-primary-hover"
                >
                    Consultar propiedad
                    <span aria-hidden="true" className="pl-1">
                        ↗
                    </span>
                </a>
            </div>
        </>
    );
}
