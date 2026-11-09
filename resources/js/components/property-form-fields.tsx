import type { ReactNode } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    propertyCurrencyOptions,
    propertyOperationOptions,
    propertyTypeOptions,
} from '@/lib/property-options';

export type PropertyFormDefaults = {
    operation?: string;
    type?: string;
    title?: string;
    description?: string;
    price?: string;
    currency?: string;
    city?: string;
    zone?: string;
    bedrooms?: number;
    bathrooms?: number;
    area?: string;
};

function RequiredMark() {
    return (
        <span className="text-brand-warm-foreground" aria-hidden="true">
            {' '}
            *
        </span>
    );
}

export function PropertySection({
    number,
    title,
    description,
    children,
}: {
    number: string;
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <section className="scroll-mt-4 rounded-xl border border-border bg-card p-5 shadow-xs sm:p-6">
            <div className="mb-5 flex gap-3">
                <span className="grid size-8 shrink-0 place-items-center rounded-[10px] bg-secondary text-xs font-bold text-secondary-foreground">
                    {number}
                </span>
                <div className="min-w-0 space-y-1">
                    <h3 className="font-display text-[15px] font-semibold tracking-tight">
                        {title}
                    </h3>
                    <p className="text-[13px] leading-snug text-muted-foreground">
                        {description}
                    </p>
                </div>
            </div>

            <div className="space-y-4">{children}</div>
        </section>
    );
}

function SegmentedChoice({
    name,
    label,
    options,
    defaultValue,
}: {
    name: 'operation' | 'type';
    label: string;
    options: ReadonlyArray<{ value: string; label: string }>;
    defaultValue: string;
}) {
    return (
        <div className="grid gap-2">
            <span className="text-[13px] font-semibold">
                {label}
                <RequiredMark />
            </span>
            <div className="grid grid-cols-2 gap-2">
                {options.map((option) => (
                    <label key={option.value} className="relative min-w-0">
                        <input
                            type="radio"
                            name={name}
                            value={option.value}
                            defaultChecked={defaultValue === option.value}
                            className="peer sr-only"
                        />
                        <span className="flex h-11 cursor-pointer items-center justify-center rounded-lg border border-input bg-card px-2 text-center text-[13px] font-semibold text-muted-foreground transition-colors peer-checked:border-primary peer-checked:bg-secondary peer-checked:text-secondary-foreground peer-focus-visible:ring-[3px] peer-focus-visible:ring-ring/50">
                            {option.label}
                        </span>
                    </label>
                ))}
            </div>
        </div>
    );
}

function CurrencySelect({
    defaultValue,
    hasError,
}: {
    defaultValue?: string;
    hasError: boolean;
}) {
    const [currency, setCurrency] = useState(defaultValue ?? 'PEN');

    return (
        <>
            <input type="hidden" name="currency" value={currency} />
            <Select value={currency} onValueChange={setCurrency}>
                <SelectTrigger
                    aria-label="Moneda"
                    aria-invalid={hasError}
                    className="h-11 w-[112px] shrink-0 rounded-r-none border-input bg-card text-[13px] font-semibold shadow-none data-[size=default]:h-11 sm:w-[125px]"
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {propertyCurrencyOptions.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </>
    );
}

export default function PropertyFormFields({
    errors,
    defaults = {},
}: {
    errors: Record<string, string>;
    defaults?: PropertyFormDefaults;
}) {
    return (
        <>
            <PropertySection
                number="01"
                title="Información general"
                description="Cuéntanos qué tipo de propiedad vas a publicar."
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <SegmentedChoice
                        name="operation"
                        label="Operación"
                        options={propertyOperationOptions}
                        defaultValue={defaults.operation ?? 'sale'}
                    />
                    <SegmentedChoice
                        name="type"
                        label="Tipo de propiedad"
                        options={propertyTypeOptions}
                        defaultValue={defaults.type ?? 'house'}
                    />
                </div>

                <div className="grid gap-2">
                    <Label
                        htmlFor="title"
                        className="text-[13px] font-semibold"
                    >
                        Título del anuncio
                        <RequiredMark />
                    </Label>
                    <Input
                        id="title"
                        name="title"
                        maxLength={255}
                        defaultValue={defaults.title}
                        aria-invalid={errors.title !== undefined}
                        aria-required="true"
                        placeholder="Ej. Departamento luminoso en Miraflores"
                        className="h-11 rounded-lg bg-card text-sm shadow-none"
                    />
                    <p className="text-xs leading-relaxed text-muted-foreground">
                        Destaca lo que hace especial a la propiedad.
                    </p>
                    <InputError message={errors.title} />
                </div>

                <div className="grid gap-2">
                    <Label
                        htmlFor="description"
                        className="text-[13px] font-semibold"
                    >
                        Descripción
                        <RequiredMark />
                    </Label>
                    <Textarea
                        id="description"
                        name="description"
                        rows={5}
                        defaultValue={defaults.description}
                        aria-invalid={errors.description !== undefined}
                        aria-required="true"
                        placeholder="Describe los ambientes, acabados y lo que encontrarán cerca..."
                        className="min-h-28 rounded-lg bg-card text-sm shadow-none"
                    />
                    <InputError message={errors.description} />
                </div>

                <InputError message={errors.operation} />
                <InputError message={errors.type} />
            </PropertySection>

            <PropertySection
                number="02"
                title="Precio y ubicación"
                description="Indica el precio y dónde se encuentra."
            >
                <div className="grid gap-2">
                    <Label
                        htmlFor="price"
                        className="text-[13px] font-semibold"
                    >
                        Precio
                        <RequiredMark />
                    </Label>
                    <div className="flex">
                        <CurrencySelect
                            defaultValue={defaults.currency}
                            hasError={errors.currency !== undefined}
                        />
                        <Input
                            id="price"
                            name="price"
                            type="number"
                            min="0.01"
                            step="0.01"
                            defaultValue={defaults.price}
                            aria-invalid={errors.price !== undefined}
                            aria-required="true"
                            placeholder="0.00"
                            className="h-11 rounded-l-none border-l-0 bg-card text-sm shadow-none focus-visible:relative focus-visible:z-10"
                        />
                    </div>
                    <p className="text-xs leading-relaxed text-muted-foreground">
                        Una moneda por propiedad, sin conversión automática.
                    </p>
                    <InputError message={errors.price} />
                    <InputError message={errors.currency} />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label
                            htmlFor="city"
                            className="text-[13px] font-semibold"
                        >
                            Ciudad
                            <RequiredMark />
                        </Label>
                        <Input
                            id="city"
                            name="city"
                            defaultValue={defaults.city}
                            aria-invalid={errors.city !== undefined}
                            aria-required="true"
                            placeholder="Ej. Lima"
                            className="h-11 rounded-lg bg-card text-sm shadow-none"
                        />
                        <InputError message={errors.city} />
                    </div>

                    <div className="grid gap-2">
                        <Label
                            htmlFor="zone"
                            className="text-[13px] font-semibold"
                        >
                            Zona
                            <RequiredMark />
                        </Label>
                        <Input
                            id="zone"
                            name="zone"
                            defaultValue={defaults.zone}
                            aria-invalid={errors.zone !== undefined}
                            aria-required="true"
                            placeholder="Ej. Miraflores"
                            className="h-11 rounded-lg bg-card text-sm shadow-none"
                        />
                        <InputError message={errors.zone} />
                    </div>
                </div>
            </PropertySection>

            <PropertySection
                number="03"
                title="Características"
                description="Los detalles que ayudan a encontrar la propiedad ideal."
            >
                <div className="grid grid-cols-3 gap-2.5 sm:gap-4">
                    <div className="grid gap-2">
                        <Label
                            htmlFor="bedrooms"
                            className="text-[13px] font-semibold"
                        >
                            Dormitorios
                            <RequiredMark />
                        </Label>
                        <Input
                            id="bedrooms"
                            name="bedrooms"
                            type="number"
                            min="0"
                            step="1"
                            defaultValue={defaults.bedrooms}
                            aria-invalid={errors.bedrooms !== undefined}
                            aria-required="true"
                            placeholder="0"
                            className="h-11 rounded-lg bg-card px-2 text-sm shadow-none sm:px-3"
                        />
                        <InputError message={errors.bedrooms} />
                    </div>

                    <div className="grid gap-2">
                        <Label
                            htmlFor="bathrooms"
                            className="text-[13px] font-semibold"
                        >
                            Baños
                            <RequiredMark />
                        </Label>
                        <Input
                            id="bathrooms"
                            name="bathrooms"
                            type="number"
                            min="0"
                            step="1"
                            defaultValue={defaults.bathrooms}
                            aria-invalid={errors.bathrooms !== undefined}
                            aria-required="true"
                            placeholder="0"
                            className="h-11 rounded-lg bg-card px-2 text-sm shadow-none sm:px-3"
                        />
                        <InputError message={errors.bathrooms} />
                    </div>

                    <div className="grid gap-2">
                        <Label
                            htmlFor="area"
                            className="text-[13px] font-semibold"
                        >
                            Área (m²)
                            <RequiredMark />
                        </Label>
                        <Input
                            id="area"
                            name="area"
                            type="number"
                            min="0.01"
                            step="0.01"
                            defaultValue={defaults.area}
                            aria-invalid={errors.area !== undefined}
                            aria-required="true"
                            placeholder="0"
                            className="h-11 rounded-lg bg-card px-2 text-sm shadow-none sm:px-3"
                        />
                        <InputError message={errors.area} />
                    </div>
                </div>
            </PropertySection>
        </>
    );
}
