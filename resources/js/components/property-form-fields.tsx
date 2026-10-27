import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import {
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

export default function PropertyFormFields({
    errors,
    defaults = {},
}: {
    errors: Record<string, string>;
    defaults?: PropertyFormDefaults;
}) {
    return (
        <>
            <section className="space-y-4">
                <h2 className="text-base font-semibold">
                    Datos de la propiedad
                </h2>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="operation">Operación</Label>
                        <NativeSelect
                            id="operation"
                            name="operation"
                            defaultValue={defaults.operation ?? 'sale'}
                        >
                            {propertyOperationOptions.map((option) => (
                                <option key={option.value} value={option.value}>
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
                            defaultValue={defaults.type ?? 'house'}
                        >
                            {propertyTypeOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={errors.type} />
                    </div>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="title">Título</Label>
                    <Input
                        id="title"
                        name="title"
                        maxLength={255}
                        defaultValue={defaults.title}
                        placeholder="Casa frente al parque"
                    />
                    <InputError message={errors.title} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="description">Descripción</Label>
                    <Textarea
                        id="description"
                        name="description"
                        rows={5}
                        defaultValue={defaults.description}
                        placeholder="Describe la propiedad, sus ambientes y acabados."
                    />
                    <InputError message={errors.description} />
                </div>
            </section>

            <section className="space-y-4">
                <h2 className="text-base font-semibold">Precio y ubicación</h2>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="price">Precio</Label>
                        <Input
                            id="price"
                            name="price"
                            type="number"
                            min="0.01"
                            step="0.01"
                            defaultValue={defaults.price}
                            placeholder="250000"
                        />
                        <InputError message={errors.price} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="currency">Moneda</Label>
                        <NativeSelect
                            id="currency"
                            name="currency"
                            defaultValue={defaults.currency ?? 'PEN'}
                        >
                            <option value="PEN">Soles (PEN)</option>
                            <option value="USD">Dólares (USD)</option>
                        </NativeSelect>
                        <InputError message={errors.currency} />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="city">Ciudad</Label>
                        <Input
                            id="city"
                            name="city"
                            defaultValue={defaults.city}
                            placeholder="Lima"
                        />
                        <InputError message={errors.city} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="zone">Zona</Label>
                        <Input
                            id="zone"
                            name="zone"
                            defaultValue={defaults.zone}
                            placeholder="Miraflores"
                        />
                        <InputError message={errors.zone} />
                    </div>
                </div>
            </section>

            <section className="space-y-4">
                <h2 className="text-base font-semibold">Detalles</h2>

                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="grid gap-2">
                        <Label htmlFor="bedrooms">Dormitorios</Label>
                        <Input
                            id="bedrooms"
                            name="bedrooms"
                            type="number"
                            min="0"
                            step="1"
                            defaultValue={defaults.bedrooms}
                            placeholder="3"
                        />
                        <InputError message={errors.bedrooms} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="bathrooms">Baños</Label>
                        <Input
                            id="bathrooms"
                            name="bathrooms"
                            type="number"
                            min="0"
                            step="1"
                            defaultValue={defaults.bathrooms}
                            placeholder="2"
                        />
                        <InputError message={errors.bathrooms} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="area">Área (m²)</Label>
                        <Input
                            id="area"
                            name="area"
                            type="number"
                            min="0.01"
                            step="0.01"
                            defaultValue={defaults.area}
                            placeholder="120.50"
                        />
                        <InputError message={errors.area} />
                    </div>
                </div>
            </section>
        </>
    );
}
