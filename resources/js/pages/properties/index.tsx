import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Building2, MapPin, SearchX } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import {
    propertyOperationOptions,
    propertyTypeOptions,
    type PropertyOperationValue,
    type PropertyTypeValue,
} from '@/lib/property-options';
import { home } from '@/routes';

type PropertyCard = {
    id: number;
    title: string;
    operation_label: string;
    type_label: string;
    price_formatted: string;
    city: string;
    zone: string;
    cover_photo_url: string | null;
    url: string;
};

type CatalogFilters = {
    operation: PropertyOperationValue | null;
    type: PropertyTypeValue | null;
    zone: string | null;
};

export default function PropertiesIndex({
    properties,
    zones,
    filters,
}: {
    properties: PropertyCard[];
    zones: string[];
    filters: CatalogFilters;
}) {
    const { errors } = usePage().props;

    const hasActiveFilters = Boolean(
        filters.operation || filters.type || filters.zone,
    );

    const zoneOptions =
        filters.zone && !zones.includes(filters.zone)
            ? [...zones, filters.zone].sort((a, b) =>
                  a.localeCompare(b, 'es', { sensitivity: 'base' }),
              )
            : zones;

    return (
        <>
            <Head title="Propiedades" />

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Propiedades
                </h1>
                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Casas y departamentos en venta y alquiler.
                </p>
            </div>

            <Form
                key={`${filters.operation ?? ''}-${filters.type ?? ''}-${filters.zone ?? ''}`}
                {...home.form()}
                className="mb-8 flex flex-col gap-4 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                {({ processing }) => (
                    <>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <div className="grid gap-2">
                                <Label htmlFor="operation">Operación</Label>
                                <NativeSelect
                                    id="operation"
                                    name="operation"
                                    defaultValue={filters.operation ?? ''}
                                >
                                    <option value="">Todas</option>
                                    {propertyOperationOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError message={errors.operation} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="type">Tipo</Label>
                                <NativeSelect
                                    id="type"
                                    name="type"
                                    defaultValue={filters.type ?? ''}
                                >
                                    <option value="">Todos</option>
                                    {propertyTypeOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError message={errors.type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="zone">Zona</Label>
                                <NativeSelect
                                    id="zone"
                                    name="zone"
                                    defaultValue={filters.zone ?? ''}
                                >
                                    <option value="">Todas</option>
                                    {zoneOptions.map((zone) => (
                                        <option key={zone} value={zone}>
                                            {zone}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError message={errors.zone} />
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-2">
                            <Button variant="ghost" asChild>
                                <Link href={home()}>Limpiar</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Filtrar
                            </Button>
                        </div>
                    </>
                )}
            </Form>

            {properties.length === 0 ? (
                hasActiveFilters ? (
                    <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-neutral-300 py-20 text-center dark:border-neutral-700">
                        <SearchX className="mb-3 size-10 text-neutral-400" />
                        <p className="font-medium">
                            No hay propiedades para esos filtros.
                        </p>
                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Prueba con otros criterios o limpia los filtros para
                            ver todo el catálogo.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href={home()}>Limpiar filtros</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-neutral-300 py-20 text-center dark:border-neutral-700">
                        <Building2 className="mb-3 size-10 text-neutral-400" />
                        <p className="font-medium">
                            No hay propiedades disponibles.
                        </p>
                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Vuelve pronto para ver las nuevas propiedades.
                        </p>
                    </div>
                )
            ) : (
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {properties.map((property) => (
                        <Link
                            key={property.id}
                            href={property.url}
                            className="group overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm transition-shadow hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <div className="aspect-[4/3] overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                                {property.cover_photo_url ? (
                                    <img
                                        src={property.cover_photo_url}
                                        alt={property.title}
                                        className="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="flex size-full items-center justify-center text-neutral-400">
                                        <Building2 className="size-10" />
                                    </div>
                                )}
                            </div>
                            <div className="space-y-3 p-4">
                                <div className="flex items-center justify-between gap-2 text-xs font-medium">
                                    <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                                        {property.operation_label}
                                    </span>
                                    <span className="text-neutral-500 dark:text-neutral-400">
                                        {property.type_label}
                                    </span>
                                </div>
                                <div>
                                    <h2 className="line-clamp-2 font-semibold">
                                        {property.title}
                                    </h2>
                                    <p className="mt-1 flex items-center gap-1 text-sm text-neutral-500 dark:text-neutral-400">
                                        <MapPin className="size-3.5" />
                                        {property.city} · {property.zone}
                                    </p>
                                </div>
                                <p className="text-lg font-semibold">
                                    {property.price_formatted}
                                </p>
                            </div>
                        </Link>
                    ))}
                </div>
            )}
        </>
    );
}
