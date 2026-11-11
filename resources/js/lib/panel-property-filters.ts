import type { PropertyStatusValue } from '@/components/property-status-badge';
import {
    provincesForDepartment,
    type UbigeoDepartment,
    type UbigeoDistrict,
    type UbigeoProvince,
} from '@/lib/ubigeo';
import {
    type PropertyCurrencyValue,
    type PropertyOperationValue,
    type PropertyTypeValue,
} from '@/lib/property-options';

export type PanelPropertySort = 'recent' | 'title' | 'price-asc' | 'price-desc';

export type PanelPropertyStatusFilter = Exclude<
    PropertyStatusValue,
    'archived'
>;

export type PanelPropertyFiltersValue = {
    search: string | null;
    status: PanelPropertyStatusFilter | null;
    operation: PropertyOperationValue | null;
    type: PropertyTypeValue | null;
    department_code: string | null;
    province_code: string | null;
    district_code: string | null;
    currency: PropertyCurrencyValue | null;
    min_price: string | null;
    max_price: string | null;
    bedrooms: number | null;
    sort: PanelPropertySort;
};

/**
 * District offered by the panel, with its province name so districts that share
 * a name stay distinguishable while no province is chosen.
 */
export type PanelDistrict = UbigeoDistrict & {
    province: string;
};

/**
 * Locations the panel may offer. They come from the properties the user can
 * manage, never from the whole catalog or from another agent's inventory.
 */
export type PanelLocationOptions = {
    departments: UbigeoDepartment[];
    provinces: UbigeoProvince[];
    districts: PanelDistrict[];
};

export type PanelLocationCodes = {
    department_code: string | null;
    province_code: string | null;
    district_code: string | null;
};

export const panelPropertySortOptions: {
    value: PanelPropertySort;
    label: string;
}[] = [
    { value: 'recent', label: 'Más recientes' },
    { value: 'title', label: 'Título A–Z' },
    { value: 'price-asc', label: 'Precio: menor primero' },
    { value: 'price-desc', label: 'Precio: mayor primero' },
];

export const PANEL_PRICE_RANGE_ERROR =
    'El precio mínimo no puede ser mayor que el precio máximo.';

export function isPriceSort(sort: PanelPropertySort): boolean {
    return sort === 'price-asc' || sort === 'price-desc';
}

export function isPanelPropertySort(value: string): value is PanelPropertySort {
    return panelPropertySortOptions.some((option) => option.value === value);
}

/**
 * Build the query the panel understands, leaving out empty filters and the
 * default order so shared URLs stay short.
 */
export function panelFiltersToQuery(
    filters: PanelPropertyFiltersValue,
): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.search !== null) {
        query.search = filters.search;
    }

    if (filters.status !== null) {
        query.status = filters.status;
    }

    if (filters.operation !== null) {
        query.operation = filters.operation;
    }

    if (filters.type !== null) {
        query.type = filters.type;
    }

    if (filters.department_code !== null) {
        query.department_code = filters.department_code;
    }

    if (filters.province_code !== null) {
        query.province_code = filters.province_code;
    }

    if (filters.district_code !== null) {
        query.district_code = filters.district_code;
    }

    if (filters.currency !== null) {
        query.currency = filters.currency;

        if (filters.min_price !== null) {
            query.min_price = filters.min_price;
        }

        if (filters.max_price !== null) {
            query.max_price = filters.max_price;
        }
    }

    if (filters.bedrooms !== null) {
        query.bedrooms = String(filters.bedrooms);
    }

    if (filters.sort !== 'recent') {
        query.sort = filters.sort;
    }

    return query;
}

export function hasActivePanelFilters(
    filters: PanelPropertyFiltersValue,
): boolean {
    return (
        filters.search !== null ||
        filters.status !== null ||
        filters.operation !== null ||
        filters.type !== null ||
        filters.department_code !== null ||
        filters.province_code !== null ||
        filters.district_code !== null ||
        filters.currency !== null ||
        filters.min_price !== null ||
        filters.max_price !== null ||
        filters.bedrooms !== null
    );
}

/**
 * Provinces offered for a department. Without a department the panel offers
 * every accessible province, so each level can filter on its own.
 */
export function panelProvinces(
    locations: PanelLocationOptions,
    departmentCode: string | null,
): UbigeoProvince[] {
    return departmentCode === null
        ? locations.provinces
        : provincesForDepartment(locations, departmentCode);
}

/**
 * Districts offered for a department and a province. A chosen province limits
 * them to its own districts; a department without a province keeps only the
 * districts that belong to it.
 */
export function panelDistricts(
    locations: PanelLocationOptions,
    departmentCode: string | null,
    provinceCode: string | null,
): PanelDistrict[] {
    if (provinceCode !== null) {
        return locations.districts.filter(
            (district) => district.province_code === provinceCode,
        );
    }

    if (departmentCode === null) {
        return locations.districts;
    }

    const provinceCodes = provincesForDepartment(locations, departmentCode).map(
        (province) => province.code,
    );

    return locations.districts.filter((district) =>
        provinceCodes.includes(district.province_code),
    );
}

/**
 * Drop the chosen levels that no longer belong to the level above, so the
 * controls never show a province or a district from another parent.
 */
export function reconcilePanelLocation(
    locations: PanelLocationOptions,
    selection: PanelLocationCodes,
): PanelLocationCodes {
    const province =
        selection.province_code !== null &&
        panelProvinces(locations, selection.department_code).some(
            (option) => option.code === selection.province_code,
        )
            ? selection.province_code
            : null;

    const district =
        selection.district_code !== null &&
        panelDistricts(locations, selection.department_code, province).some(
            (option) => option.code === selection.district_code,
        )
            ? selection.district_code
            : null;

    return {
        department_code: selection.department_code,
        province_code: province,
        district_code: district,
    };
}
