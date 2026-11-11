import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Combobox } from '@/components/ui/combobox';
import type { UbigeoCatalogState } from '@/hooks/use-ubigeo-catalog';
import {
    districtsForProvince,
    provincesForDepartment,
    reconcileLocationCodes,
    type PropertyLocationCodes,
} from '@/lib/ubigeo';

const triggerClassName =
    'h-11 w-full rounded-lg border-input bg-card text-sm font-normal shadow-none data-[size=default]:h-11';

function RequiredMark() {
    return (
        <span className="text-brand-warm-foreground" aria-hidden="true">
            {' '}
            *
        </span>
    );
}

function LocationSelect({
    id,
    label,
    placeholder,
    value,
    options,
    disabled,
    invalid,
    onChange,
}: {
    id: string;
    label: string;
    placeholder: string;
    value: string;
    options: ReadonlyArray<{ code: string; name: string }>;
    disabled: boolean;
    invalid: boolean;
    onChange: (code: string) => void;
}) {
    return (
        <div className="grid gap-2">
            <Label
                id={`${id}-label`}
                htmlFor={id}
                className="text-[13px] font-semibold"
            >
                {label}
                <span className="sr-only"> (obligatorio)</span>
                <RequiredMark />
            </Label>
            <Combobox
                id={id}
                value={value}
                onValueChange={onChange}
                options={options}
                placeholder={placeholder}
                searchPlaceholder="Buscar ubicación..."
                searchLabel={`Buscar ${label.toLowerCase()}`}
                dialogLabel={`Seleccionar ${label.toLowerCase()}`}
                labelId={`${id}-label`}
                emptyMessage="No se encontraron ubicaciones."
                disabled={disabled}
                invalid={invalid}
                aria-describedby={invalid ? `${id}-error` : undefined}
                className={triggerClassName}
            />
        </div>
    );
}

export default function PropertyLocationFields({
    catalogState,
    defaults = {},
    errors,
}: {
    catalogState: UbigeoCatalogState;
    defaults?: {
        department_code?: string;
        province_code?: string;
        district_code?: string;
    };
    errors: Record<string, string>;
}) {
    const { status, catalog, error, retry } = catalogState;
    const isReady = status === 'ready' && catalog !== null;
    const catalogPlaceholder =
        status === 'loading' ? 'Cargando…' : 'No disponible';

    const [location, setLocation] = useState<PropertyLocationCodes>(() => ({
        department: defaults.department_code ?? '',
        province: defaults.province_code ?? '',
        district: defaults.district_code ?? '',
    }));

    useEffect(() => {
        if (catalog === null) {
            return;
        }

        setLocation((current) => reconcileLocationCodes(catalog, current));
    }, [catalog]);

    const provinces =
        catalog === null
            ? []
            : provincesForDepartment(catalog, location.department);
    const districts =
        catalog === null
            ? []
            : districtsForProvince(catalog, location.province);

    return (
        <div className="grid gap-3">
            {status === 'error' && (
                <div
                    role="alert"
                    className="rounded-lg border border-destructive/30 bg-destructive/5 p-3"
                >
                    <p className="text-[13px] font-semibold text-destructive">
                        {error}
                    </p>
                    <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                        Sin el catálogo no se puede elegir ni guardar la
                        ubicación de la propiedad.
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="mt-2.5 rounded-lg border-input text-xs font-semibold"
                        onClick={retry}
                    >
                        Reintentar
                    </Button>
                </div>
            )}

            {status === 'loading' && (
                <p
                    role="status"
                    className="text-xs leading-relaxed text-muted-foreground"
                >
                    Cargando el catálogo de ubicaciones…
                </p>
            )}

            <input
                type="hidden"
                name="department_code"
                value={location.department}
            />
            <input
                type="hidden"
                name="province_code"
                value={location.province}
            />
            <input
                type="hidden"
                name="district_code"
                value={location.district}
            />

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="grid gap-2">
                    <LocationSelect
                        id="property-department"
                        label="Departamento"
                        placeholder={
                            isReady
                                ? 'Elige un departamento'
                                : catalogPlaceholder
                        }
                        value={location.department}
                        options={catalog?.departments ?? []}
                        disabled={!isReady}
                        invalid={errors.department_code !== undefined}
                        onChange={(department) =>
                            setLocation((current) =>
                                department === current.department
                                    ? current
                                    : {
                                          department,
                                          province: '',
                                          district: '',
                                      },
                            )
                        }
                    />
                    <InputError
                        id="property-department-error"
                        message={errors.department_code}
                    />
                </div>

                <div className="grid gap-2">
                    <LocationSelect
                        id="property-province"
                        label="Provincia"
                        placeholder={
                            !isReady
                                ? catalogPlaceholder
                                : location.department === ''
                                  ? 'Elige un departamento'
                                  : 'Elige una provincia'
                        }
                        value={location.province}
                        options={provinces}
                        disabled={!isReady || location.department === ''}
                        invalid={errors.province_code !== undefined}
                        onChange={(province) =>
                            setLocation((current) =>
                                province === current.province
                                    ? current
                                    : { ...current, province, district: '' },
                            )
                        }
                    />
                    <InputError
                        id="property-province-error"
                        message={errors.province_code}
                    />
                </div>

                <div className="grid gap-2">
                    <LocationSelect
                        id="property-district"
                        label="Distrito"
                        placeholder={
                            !isReady
                                ? catalogPlaceholder
                                : location.province === ''
                                  ? 'Elige una provincia'
                                  : 'Elige un distrito'
                        }
                        value={location.district}
                        options={districts}
                        disabled={!isReady || location.province === ''}
                        invalid={errors.district_code !== undefined}
                        onChange={(district) =>
                            setLocation((current) => ({
                                ...current,
                                district,
                            }))
                        }
                    />
                    <InputError
                        id="property-district-error"
                        message={errors.district_code}
                    />
                </div>
            </div>
        </div>
    );
}
