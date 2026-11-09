type SidebarNote = {
    path: string;
    note: string;
};

/**
 * Context note shown above the account block of the panel sidebar.
 *
 * The first path that matches the current URL wins, so keep specific paths
 * above the general ones.
 */
const sidebarNotes: SidebarNote[] = [
    {
        path: '/dashboard',
        note: 'Panel VillaJato. Gestiona tus propiedades y atiende las consultas.',
    },
    {
        path: '/panel/properties',
        note: 'Las fichas publicadas. Edita, cierra o archiva desde el detalle.',
    },
    {
        path: '/panel/archived-properties',
        note: 'Fichas fuera del catálogo. Republica o elimina cuando lo decidas.',
    },
    {
        path: '/panel/inquiries',
        note: 'Consultas desde tus fichas. Personas con un próximo paso.',
    },
    {
        path: '/panel/agents/create',
        note: 'Una cuenta nueva. Cada agente gestiona sus propiedades y sus consultas.',
    },
    {
        path: '/settings',
        note: 'Tu cuenta y tu acceso. Datos, seguridad y apariencia.',
    },
];

export function sidebarNoteFor(pathname: string): string | null {
    const match = sidebarNotes.find(
        ({ path }) => pathname === path || pathname.startsWith(`${path}/`),
    );

    return match?.note ?? null;
}
