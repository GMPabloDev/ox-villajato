export type UbigeoDepartment = {
    code: string;
    name: string;
};

export type UbigeoProvince = UbigeoDepartment & {
    department_code: string;
};

export type UbigeoDistrict = UbigeoDepartment & {
    province_code: string;
};

export type UbigeoCatalog = {
    departments: UbigeoDepartment[];
    provinces: UbigeoProvince[];
    districts: UbigeoDistrict[];
};

/**
 * Codes chosen in the property form. An empty string means "not chosen".
 */
export type PropertyLocationCodes = {
    department: string;
    province: string;
    district: string;
};

export type PropertyLocationNames = {
    district: string;
    province: string;
    department: string;
};

export const UBIGEO_UNAVAILABLE_MESSAGE =
    'No se pudo cargar el catálogo de ubicaciones.';

export const EMPTY_LOCATION: PropertyLocationCodes = {
    department: '',
    province: '',
    district: '',
};

export function isUbigeoCatalog(value: unknown): value is UbigeoCatalog {
    if (typeof value !== 'object' || value === null) {
        return false;
    }

    const candidate = value as Record<string, unknown>;
    const validEntry = (entry: unknown): entry is Record<string, string> =>
        typeof entry === 'object' &&
        entry !== null &&
        typeof (entry as Record<string, unknown>).code === 'string' &&
        (entry as Record<string, string>).code.length > 0 &&
        typeof (entry as Record<string, unknown>).name === 'string' &&
        (entry as Record<string, string>).name.length > 0;

    if (
        !Array.isArray(candidate.departments) ||
        candidate.departments.length === 0 ||
        !Array.isArray(candidate.provinces) ||
        candidate.provinces.length === 0 ||
        !Array.isArray(candidate.districts) ||
        candidate.districts.length === 0 ||
        !candidate.departments.every(validEntry) ||
        !candidate.provinces.every(validEntry) ||
        !candidate.districts.every(validEntry)
    ) {
        return false;
    }

    const departments = new Set(
        candidate.departments.map((entry) => entry.code),
    );
    const provinces = new Set(candidate.provinces.map((entry) => entry.code));
    return (
        candidate.provinces.every((entry) =>
            departments.has(entry.department_code),
        ) &&
        candidate.districts.every((entry) => provinces.has(entry.province_code))
    );
}

export function provincesForDepartment(
    catalog: UbigeoCatalog,
    departmentCode: string,
): UbigeoProvince[] {
    if (departmentCode === '') {
        return [];
    }

    return catalog.provinces.filter(
        (province) => province.department_code === departmentCode,
    );
}

export function districtsForProvince(
    catalog: UbigeoCatalog,
    provinceCode: string,
): UbigeoDistrict[] {
    if (provinceCode === '') {
        return [];
    }

    return catalog.districts.filter(
        (district) => district.province_code === provinceCode,
    );
}

/**
 * Drop the chosen levels whose parent is missing from the catalog, so a form
 * never submits a district that does not belong to the chosen province.
 */
export function reconcileLocationCodes(
    catalog: UbigeoCatalog,
    selection: PropertyLocationCodes,
): PropertyLocationCodes {
    const department = catalog.departments.some(
        (entry) => entry.code === selection.department,
    )
        ? selection.department
        : '';

    const province =
        department !== '' &&
        catalog.provinces.some(
            (entry) =>
                entry.code === selection.province &&
                entry.department_code === department,
        )
            ? selection.province
            : '';

    const district =
        province !== '' &&
        catalog.districts.some(
            (entry) =>
                entry.code === selection.district &&
                entry.province_code === province,
        )
            ? selection.district
            : '';

    return { department, province, district };
}

/**
 * Readable location of a property: district, province and department, so
 * districts that share a name stay distinguishable.
 */
export function locationLabel(location: PropertyLocationNames): string {
    return `${location.district}, ${location.province} · ${location.department}`;
}
