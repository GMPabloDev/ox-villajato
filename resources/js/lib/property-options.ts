export const propertyOperationOptions = [
    { value: 'sale', label: 'Venta' },
    { value: 'rent', label: 'Alquiler' },
] as const;

export const propertyTypeOptions = [
    { value: 'house', label: 'Casa' },
    { value: 'apartment', label: 'Departamento' },
] as const;

export type PropertyOperationValue =
    (typeof propertyOperationOptions)[number]['value'];

export type PropertyTypeValue = (typeof propertyTypeOptions)[number]['value'];
