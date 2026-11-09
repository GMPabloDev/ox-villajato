export function workspaceLabel(isAdmin: boolean): string {
    return isAdmin ? 'Espacio de administración' : 'Espacio de agente';
}
