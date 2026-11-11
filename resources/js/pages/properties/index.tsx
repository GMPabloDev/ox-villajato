import { Head, Link, router } from '@inertiajs/react';
import { Building2, SearchX } from 'lucide-react';
import { useEffect, useState } from 'react';
import FilterMenu, { FilterOption } from '@/components/filter-menu';
import PropertyGalleryCard from '@/components/property-gallery-card';
import PublicContainer from '@/components/public-container';
import {
    propertyOperationOptions,
    propertyTypeOptions,
    type PropertyOperationValue,
    type PropertyTypeValue,
} from '@/lib/property-options';
import { locationLabel } from '@/lib/ubigeo';
import { cn } from '@/lib/utils';
import { home } from '@/routes';

type PropertyCard = {
    id: number;
    title: string;
    operation: PropertyOperationValue;
    operation_label: string;
    type: PropertyTypeValue;
    type_label: string;
    price_formatted: string;
    district: string;
    province: string;
    department: string;
    bedrooms: number;
    bathrooms: number;
    area: string;
    cover_photo_url: string | null;
    url: string;
};

type CatalogDepartment = {
    code: string;
    name: string;
};

type CatalogProvince = CatalogDepartment & {
    department_code: string;
};

type CatalogDistrict = CatalogDepartment & {
    province_code: string;
    province: string;
    department: string;
};

/**
 * Locations with available properties offered by the public filters, from the
 * department down to the district.
 */
type CatalogLocations = {
    departments: CatalogDepartment[];
    provinces: CatalogProvince[];
    districts: CatalogDistrict[];
};

/**
 * Names of the selected location, resolved by the server. They stay available
 * when the selected location no longer has properties on offer.
 */
type AppliedLocation = {
    department: string | null;
    province: string | null;
    district: string | null;
};

type FilterMenuId = 'type' | 'department' | 'province' | 'district';

/**
 * District, province and department, so districts that share a name stay
 * distinguishable in the public filter.
 */
function districtLabel(district: CatalogDistrict): string {
    return locationLabel({
        district: district.name,
        province: district.province,
        department: district.department,
    });
}

/**
 * Readable label of the applied district when it has no available properties
 * and therefore does not appear among the location options.
 */
function appliedDistrictLabel(appliedLocation: AppliedLocation): string | null {
    if (
        appliedLocation.district === null ||
        appliedLocation.province === null ||
        appliedLocation.department === null
    ) {
        return null;
    }

    return locationLabel({
        district: appliedLocation.district,
        province: appliedLocation.province,
        department: appliedLocation.department,
    });
}

type CatalogFilters = {
    operation: PropertyOperationValue | null;
    type: PropertyTypeValue | null;
    department_code: string | null;
    province_code: string | null;
    district_code: string | null;
};

const operationTabs: { label: string; value: PropertyOperationValue | null }[] =
    [{ label: 'Todas', value: null }, ...propertyOperationOptions];

function filtersToQuery(filters: CatalogFilters): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.operation) {
        query.operation = filters.operation;
    }

    if (filters.type) {
        query.type = filters.type;
    }

    if (filters.department_code) {
        query.department_code = filters.department_code;
    }

    if (filters.province_code) {
        query.province_code = filters.province_code;
    }

    if (filters.district_code) {
        query.district_code = filters.district_code;
    }

    return query;
}

function catalogHref(
    filters: CatalogFilters,
    operation: PropertyOperationValue | null,
): string {
    return home.url({ query: filtersToQuery({ ...filters, operation }) });
}

type LocationFilterOption = {
    value: string;
    label: string;
    disabled?: boolean;
};

/**
 * Keep the applied location visible in its menu when it is not among the
 * available locations, so a valid selection never silently disappears from the
 * controls even after its last property is gone.
 */
function withAppliedOption(
    options: LocationFilterOption[],
    code: string | null,
    appliedLabel: string | null,
): LocationFilterOption[] {
    if (code === null || options.some((option) => option.value === code)) {
        return options;
    }

    return [
        ...options,
        {
            value: code,
            label: `${appliedLabel ?? code} (filtro actual)`,
            disabled: true,
        },
    ];
}

export default function PropertiesIndex({
    properties,
    filters,
    locations,
    appliedLocation,
}: {
    properties: PropertyCard[];
    filters: CatalogFilters;
    locations: CatalogLocations;
    appliedLocation: AppliedLocation;
}) {
    const [openMenu, setOpenMenu] = useState<FilterMenuId | null>(null);

    const hasActiveFilters = Boolean(
        filters.operation ||
        filters.type ||
        filters.department_code ||
        filters.province_code ||
        filters.district_code,
    );

    const total = properties.length;
    const typeLabel =
        propertyTypeOptions.find((option) => option.value === filters.type)
            ?.label ?? 'Todos';
    const departmentValueLabel =
        locations.departments.find(
            (department) => department.code === filters.department_code,
        )?.name ??
        appliedLocation.department ??
        'Todos';
    const provinceValueLabel =
        locations.provinces.find(
            (province) => province.code === filters.province_code,
        )?.name ??
        appliedLocation.province ??
        'Todas';
    const selectedDistrict = locations.districts.find(
        (district) => district.code === filters.district_code,
    );
    const districtValueLabel =
        (selectedDistrict ? districtLabel(selectedDistrict) : null) ??
        appliedDistrictLabel(appliedLocation) ??
        'Todos';

    const departmentOptions = withAppliedOption(
        locations.departments.map((department) => ({
            value: department.code,
            label: department.name,
        })),
        filters.department_code,
        appliedLocation.department,
    );

    const provinceOptions = withAppliedOption(
        filters.department_code === null
            ? []
            : locations.provinces
                  .filter(
                      (province) =>
                          province.department_code === filters.department_code,
                  )
                  .map((province) => ({
                      value: province.code,
                      label: province.name,
                  })),
        filters.province_code,
        appliedLocation.province,
    );

    const districtOptions = withAppliedOption(
        filters.province_code === null
            ? []
            : locations.districts
                  .filter(
                      (district) =>
                          district.province_code === filters.province_code,
                  )
                  .map((district) => ({
                      value: district.code,
                      label: districtLabel(district),
                  })),
        filters.district_code,
        appliedDistrictLabel(appliedLocation),
    );

    useEffect(() => {
        if (openMenu === null) {
            return;
        }

        const handlePointerDown = (event: PointerEvent) => {
            const target = event.target as Element | null;

            if (!target?.closest(`[data-filter-menu="${openMenu}"]`)) {
                setOpenMenu(null);
            }
        };

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key !== 'Escape') {
                return;
            }

            const menu = openMenu;
            setOpenMenu(null);
            document
                .querySelector<HTMLElement>(`[data-filter-summary="${menu}"]`)
                ?.focus();
        };

        document.addEventListener('pointerdown', handlePointerDown);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('pointerdown', handlePointerDown);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [openMenu]);

    const handleMenuToggle = (menu: FilterMenuId, open: boolean) => {
        setOpenMenu((current) => {
            if (open) {
                return menu;
            }

            return current === menu ? null : current;
        });
    };

    const applyFilters = (changes: Partial<CatalogFilters>) => {
        const summary = openMenu
            ? document.querySelector<HTMLElement>(
                  `[data-filter-summary="${openMenu}"]`,
              )
            : null;
        const next: CatalogFilters = { ...filters, ...changes };

        setOpenMenu(null);
        summary?.focus();

        router.get(home.url(), filtersToQuery(next), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    /**
     * Every child level belongs to a single parent, so changing or clearing a
     * level leaves the levels below it without a valid parent. They are
     * dropped instead of keeping an incompatible selection.
     */
    const applyDepartment = (departmentCode: string | null) => {
        applyFilters({
            department_code: departmentCode,
            province_code: null,
            district_code: null,
        });
    };

    const applyProvince = (provinceCode: string | null) => {
        applyFilters({
            province_code: provinceCode,
            district_code: null,
        });
    };

    const applyDistrict = (districtCode: string | null) => {
        applyFilters({ district_code: districtCode });
    };

    const clearFilters = () => {
        setOpenMenu(null);
        router.get(
            home.url(),
            {},
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
        document.querySelector<HTMLElement>('#catalog-title')?.focus();
    };

    return (
        <>
            <Head title="Propiedades" />

            <section
                aria-labelledby="hero-title"
                className="relative isolate overflow-hidden bg-[#173e3b] text-white"
            >
                <img
                    src="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1600&q=85"
                    alt="Sala luminosa de una vivienda de ejemplo"
                    fetchPriority="high"
                    className="vj-hero-image absolute inset-0 -z-20 size-full object-cover"
                />
                <div
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 hidden bg-[linear-gradient(90deg,rgba(12,35,33,0.85),rgba(12,35,33,0.13)_85%)] md:block"
                />
                <div
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(12,35,33,0.82),rgba(12,35,33,0.35))] md:hidden"
                />
                <div
                    aria-hidden="true"
                    className="absolute inset-x-0 bottom-0 -z-10 h-40 bg-[linear-gradient(transparent,rgba(12,33,31,0.55))]"
                />

                <PublicContainer className="vj-hero-enter relative flex min-h-[600px] flex-col justify-center py-14 md:min-h-[670px] md:py-[70px]">
                    <p className="flex items-center gap-2.5 text-[11px] font-extrabold tracking-[0.16em] text-[#d0e9e1]">
                        <span
                            aria-hidden="true"
                            className="h-0.5 w-[26px] bg-brand-warm"
                        />
                        INMOBILIARIA EN PERÚ
                    </p>

                    <h1
                        id="hero-title"
                        className="mt-8 max-w-[700px] font-display text-[clamp(2.5rem,10vw,3.75rem)] leading-[1.06] font-bold tracking-[-0.045em] md:text-[clamp(2.7rem,4.5vw,4.4rem)]"
                    >
                        Tu próximo hogar,{' '}
                        <em className="text-[#a9e0d2] not-italic">
                            tu próximo gran paso.
                        </em>
                    </h1>

                    <p className="vj-hero-lead mt-6 max-w-[425px] text-[15px] leading-[1.8] text-[#eef7f4] md:text-base">
                        Hay lugares que se sienten bien desde el primer momento.
                        Encuentra el espacio para la vida que quieres construir.
                    </p>

                    <div className="vj-hero-actions mt-8 flex flex-wrap items-center gap-x-7 gap-y-4">
                        <a
                            href="#propiedades"
                            className="inline-flex min-h-[55px] items-center justify-between gap-9 rounded-[9px] bg-white px-5 text-[13px] font-bold text-[#17645f] transition-colors duration-[250ms] hover:bg-[#e6f3ef]"
                        >
                            Explorar propiedades{' '}
                            <span aria-hidden="true" className="text-[19px]">
                                ↗
                            </span>
                        </a>
                        <a
                            href="#como-funciona"
                            className="text-[13px] font-bold text-white hover:text-[#a9e0d2]"
                        >
                            Conoce VillaJato{' '}
                            <span aria-hidden="true" className="pl-2">
                                ↓
                            </span>
                        </a>
                    </div>

                    <div className="vj-hero-foot mt-10 flex items-center gap-3.5 text-[10px] leading-[1.8] tracking-[0.13em] text-white/80 md:mt-16 md:text-[11px]">
                        <span
                            aria-hidden="true"
                            className="h-px w-9 bg-brand-warm"
                        />
                        <span>
                            CASAS Y DEPARTAMENTOS
                            <br />
                            <strong className="font-bold text-white">
                                EN VENTA Y ALQUILER
                            </strong>
                        </span>
                    </div>
                </PublicContainer>

                <div className="absolute inset-x-0 bottom-6 hidden items-center justify-between px-7 text-[10px] font-bold tracking-[0.14em] md:flex">
                    <span>UN LUGAR PARA EMPEZAR</span>
                    <span>FOTO REFERENCIAL</span>
                </div>
            </section>

            <section
                id="como-funciona"
                className="scroll-mt-6 border-b border-border"
            >
                <PublicContainer className="grid grid-cols-[1fr_auto] gap-5 py-10 md:grid-cols-[1fr_2fr_auto] md:items-start md:gap-9 md:py-[58px]">
                    <p className="col-span-2 flex items-center gap-2 text-[11px] font-extrabold tracking-[0.12em] text-brand-primary md:col-span-1">
                        <span
                            aria-hidden="true"
                            className="size-[7px] rounded-full bg-brand-warm"
                        />
                        UN BUEN LUGAR PARA EMPEZAR
                    </p>

                    <p className="max-w-[700px] font-display text-[clamp(1.2rem,2vw,1.7rem)] leading-[1.45] font-semibold tracking-[-0.03em]">
                        Encontrar tu espacio puede ser más sencillo.{' '}
                        <span className="text-[#829792] dark:text-muted-foreground">
                            Explora las opciones, descubre sus detalles y
                            consulta directamente desde cada propiedad.
                        </span>
                    </p>

                    <a
                        href="#propiedades"
                        aria-label="Ir a propiedades"
                        className="grid size-12 place-items-center rounded-full border border-[#adc2ba] text-[22px] text-brand-primary transition-colors duration-[250ms] hover:border-brand-primary"
                    >
                        <span aria-hidden="true">↘</span>
                    </a>
                </PublicContainer>
            </section>

            <section
                id="propiedades"
                aria-labelledby="catalog-title"
                className="scroll-mt-6"
            >
                <PublicContainer className="pt-16 pb-20 md:pt-[100px] md:pb-[110px]">
                    <div className="flex items-end justify-between gap-5">
                        <div>
                            <p className="flex items-center gap-2.5 text-[11px] font-extrabold tracking-[0.16em] text-brand-primary">
                                <span
                                    aria-hidden="true"
                                    className="h-0.5 w-[26px] bg-brand-warm"
                                />
                                EXPLORA CON TRANQUILIDAD
                            </p>
                            <h2
                                id="catalog-title"
                                tabIndex={-1}
                                className="mt-5 mb-4 font-display text-[clamp(2.3rem,4vw,3.5rem)] leading-[1.12] font-bold tracking-[-0.045em]"
                            >
                                Un lugar para{' '}
                                <em className="text-brand-primary not-italic">
                                    cada historia.
                                </em>
                            </h2>
                            <p className="leading-relaxed text-muted-foreground">
                                Conoce algunas de nuestras propiedades en venta
                                y alquiler.
                            </p>
                        </div>
                        <span className="hidden text-[11px] tracking-[0.12em] whitespace-nowrap text-[#718984] md:block dark:text-muted-foreground">
                            01 — PROPIEDADES
                        </span>
                    </div>

                    <div className="relative z-20 mt-11 flex min-h-[76px] flex-wrap items-center gap-2.5 rounded-[14px] border border-border bg-muted px-3 py-3 md:gap-4 md:px-4">
                        <div
                            role="group"
                            aria-label="Filtrar por operación"
                            className="flex w-full items-center justify-between gap-0.5 rounded-[10px] border border-border bg-card p-1 md:w-auto md:justify-start"
                        >
                            {operationTabs.map((tab) => {
                                const isActive =
                                    filters.operation === tab.value;

                                return (
                                    <Link
                                        key={tab.label}
                                        href={catalogHref(filters, tab.value)}
                                        preserveScroll
                                        preserveState
                                        aria-current={
                                            isActive ? 'page' : undefined
                                        }
                                        className={cn(
                                            'flex min-h-[38px] flex-1 items-center justify-center rounded-[7px] px-4 text-[13px] font-semibold transition-colors duration-[250ms] md:flex-none',
                                            isActive
                                                ? 'bg-primary text-primary-foreground'
                                                : 'text-muted-foreground hover:bg-muted',
                                        )}
                                    >
                                        {tab.label}
                                    </Link>
                                );
                            })}
                        </div>

                        <div className="grid w-full grid-cols-2 gap-1.5 md:ml-auto md:flex md:w-auto md:flex-wrap md:items-center md:gap-1">
                            <FilterMenu
                                id="type"
                                label="Tipo"
                                valueLabel={typeLabel}
                                legend="Tipo de propiedad"
                                open={openMenu === 'type'}
                                onOpenChange={(open) =>
                                    handleMenuToggle('type', open)
                                }
                            >
                                <FilterOption
                                    name="property-type"
                                    value="all"
                                    label="Todos los tipos"
                                    checked={filters.type === null}
                                    onSelect={() =>
                                        applyFilters({ type: null })
                                    }
                                />
                                {propertyTypeOptions.map((option) => (
                                    <FilterOption
                                        key={option.value}
                                        name="property-type"
                                        value={option.value}
                                        label={option.label}
                                        checked={filters.type === option.value}
                                        onSelect={() =>
                                            applyFilters({
                                                type: option.value,
                                            })
                                        }
                                    />
                                ))}
                            </FilterMenu>

                            <FilterMenu
                                id="department"
                                label="Departamento"
                                valueLabel={departmentValueLabel}
                                legend="Departamento"
                                open={openMenu === 'department'}
                                align="right"
                                onOpenChange={(open) =>
                                    handleMenuToggle('department', open)
                                }
                            >
                                <FilterOption
                                    name="property-department"
                                    value="all"
                                    label="Todos los departamentos"
                                    checked={filters.department_code === null}
                                    onSelect={() => applyDepartment(null)}
                                />
                                {departmentOptions.map((option) => (
                                    <FilterOption
                                        key={option.value}
                                        name="property-department"
                                        value={option.value}
                                        label={option.label}
                                        disabled={option.disabled}
                                        checked={
                                            filters.department_code ===
                                            option.value
                                        }
                                        onSelect={() =>
                                            applyDepartment(option.value)
                                        }
                                    />
                                ))}
                            </FilterMenu>

                            <FilterMenu
                                id="province"
                                label="Provincia"
                                valueLabel={provinceValueLabel}
                                legend="Provincia"
                                open={openMenu === 'province'}
                                onOpenChange={(open) =>
                                    handleMenuToggle('province', open)
                                }
                            >
                                <FilterOption
                                    name="property-province"
                                    value="all"
                                    label="Todas las provincias"
                                    checked={filters.province_code === null}
                                    onSelect={() => applyProvince(null)}
                                />
                                {filters.department_code === null && (
                                    <p className="px-3 py-2 text-[11px] leading-snug text-muted-foreground">
                                        Elige un departamento para ver sus
                                        provincias.
                                    </p>
                                )}
                                {provinceOptions.map((option) => (
                                    <FilterOption
                                        key={option.value}
                                        name="property-province"
                                        value={option.value}
                                        label={option.label}
                                        disabled={option.disabled}
                                        checked={
                                            filters.province_code ===
                                            option.value
                                        }
                                        onSelect={() =>
                                            applyProvince(option.value)
                                        }
                                    />
                                ))}
                            </FilterMenu>

                            <FilterMenu
                                id="district"
                                label="Distrito"
                                valueLabel={districtValueLabel}
                                legend="Distrito"
                                open={openMenu === 'district'}
                                align="right"
                                onOpenChange={(open) =>
                                    handleMenuToggle('district', open)
                                }
                            >
                                <FilterOption
                                    name="property-district"
                                    value="all"
                                    label="Todos los distritos"
                                    checked={filters.district_code === null}
                                    onSelect={() => applyDistrict(null)}
                                />
                                {filters.province_code === null && (
                                    <p className="px-3 py-2 text-[11px] leading-snug text-muted-foreground">
                                        Elige una provincia para ver sus
                                        distritos.
                                    </p>
                                )}
                                {districtOptions.map((option) => (
                                    <FilterOption
                                        key={option.value}
                                        name="property-district"
                                        value={option.value}
                                        label={option.label}
                                        disabled={option.disabled}
                                        checked={
                                            filters.district_code ===
                                            option.value
                                        }
                                        onSelect={() =>
                                            applyDistrict(option.value)
                                        }
                                    />
                                ))}
                            </FilterMenu>
                        </div>

                        <button
                            type="button"
                            onClick={clearFilters}
                            className={cn(
                                'ml-auto inline-flex min-h-9 items-center justify-center gap-1.5 px-2 text-xs font-bold text-primary transition-colors duration-[250ms] hover:text-brand-primary-hover hover:underline hover:underline-offset-[3px] md:ml-0 md:min-h-11 md:min-w-[122px] md:border-l md:border-border md:px-2.5',
                                hasActiveFilters
                                    ? ''
                                    : 'invisible max-md:hidden',
                            )}
                        >
                            <span
                                aria-hidden="true"
                                className="text-xl leading-none font-normal"
                            >
                                ×
                            </span>
                            Limpiar filtros
                        </button>

                        <p aria-live="polite" className="sr-only">
                            {total === 0
                                ? 'No hay resultados.'
                                : `${total} ${total === 1 ? 'propiedad encontrada' : 'propiedades encontradas'}.`}
                        </p>
                    </div>

                    <div className="mt-8">
                        {total === 0 ? (
                            <div className="bg-muted px-6 py-16 text-center">
                                <span className="mx-auto mb-5 grid size-14 place-items-center rounded-full bg-secondary text-primary">
                                    {hasActiveFilters ? (
                                        <SearchX className="size-6" />
                                    ) : (
                                        <Building2 className="size-6" />
                                    )}
                                </span>
                                <h3 className="font-display text-2xl font-bold">
                                    {hasActiveFilters
                                        ? 'No encontramos propiedades con esos filtros'
                                        : 'Aún no hay propiedades disponibles'}
                                </h3>
                                <p className="mx-auto mt-3 max-w-md text-sm leading-6 text-muted-foreground">
                                    {hasActiveFilters
                                        ? 'Prueba con otra selección para ver más opciones.'
                                        : 'Vuelve más adelante para conocer las próximas propiedades.'}
                                </p>
                                {hasActiveFilters && (
                                    <button
                                        type="button"
                                        onClick={clearFilters}
                                        className="mt-6 inline-flex h-12 items-center rounded-[9px] bg-primary px-5 text-[13px] font-bold text-primary-foreground transition-colors duration-[250ms] hover:bg-brand-primary-hover"
                                    >
                                        Limpiar filtros
                                    </button>
                                )}
                            </div>
                        ) : (
                            <div className="flex flex-col gap-9">
                                {properties.map((property, index) => (
                                    <PropertyGalleryCard
                                        key={property.id}
                                        property={property}
                                        index={index}
                                        total={total}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                </PublicContainer>
            </section>

            <PublicContainer>
                <section
                    aria-labelledby="closing-title"
                    className="relative overflow-hidden bg-[#174c47] px-6 py-10 text-white md:px-16 md:py-14"
                >
                    <svg
                        viewBox="0 0 48 48"
                        fill="none"
                        aria-hidden="true"
                        className="absolute -top-9 -right-6 w-[190px] text-white opacity-[0.08]"
                    >
                        <path
                            d="M9 28C16 18 24 13 37 11"
                            stroke="currentColor"
                            strokeWidth="5.5"
                            strokeLinecap="round"
                        />
                        <path
                            d="M12 39C23 36 30 31 36 22"
                            stroke="currentColor"
                            strokeWidth="5.5"
                            strokeLinecap="round"
                        />
                    </svg>

                    <div className="relative grid gap-8 md:grid-cols-[1.3fr_1fr] md:items-end md:gap-9">
                        <div>
                            <p className="flex items-center gap-2.5 text-[11px] font-extrabold tracking-[0.16em] text-[#c1e6dd]">
                                <span
                                    aria-hidden="true"
                                    className="h-0.5 w-[26px] bg-brand-warm"
                                />
                                ESTAMOS PARA ACOMPAÑARTE
                            </p>
                            <h2
                                id="closing-title"
                                className="mt-5 font-display text-[clamp(2.25rem,4vw,3.5rem)] leading-[1.12] font-bold tracking-[-0.045em]"
                            >
                                El siguiente paso
                                <br />
                                empieza{' '}
                                <em className="text-[#9ed9c9] not-italic">
                                    aquí.
                                </em>
                            </h2>
                        </div>

                        <div>
                            <p className="max-w-[340px] text-[15px] leading-[1.7] text-[#d0e6df]">
                                Descubre los detalles de cada propiedad y envía
                                tu consulta desde su ficha.
                            </p>
                            <a
                                href="#propiedades"
                                className="mt-6 inline-flex min-h-[55px] items-center gap-9 rounded-[9px] bg-white px-5 text-[13px] font-bold text-[#17645f] transition-colors duration-[250ms] hover:bg-[#e6f3ef]"
                            >
                                Ver propiedades{' '}
                                <span
                                    aria-hidden="true"
                                    className="text-[19px]"
                                >
                                    ↗
                                </span>
                            </a>
                        </div>
                    </div>
                </section>
            </PublicContainer>
        </>
    );
}
