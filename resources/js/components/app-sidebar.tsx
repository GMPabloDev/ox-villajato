import { Link, usePage } from '@inertiajs/react';
import { Archive, House, Inbox, LayoutGrid, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { SidebarContextNote } from '@/components/sidebar-context-note';
import { SidebarWorkspace } from '@/components/sidebar-workspace';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarRail,
} from '@/components/ui/sidebar';
import { index as inquiriesIndex } from '@/actions/App/Http/Controllers/PropertyInquiryController';
import { dashboard } from '@/routes';
import { create as createAgent } from '@/actions/App/Http/Controllers/AgentController';
import {
    archivedIndex,
    panelIndex,
} from '@/actions/App/Http/Controllers/PropertyController';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { auth, nav } = usePage().props;
    const isAdmin = auth.user.role === 'admin';

    const managementNavItems: NavItem[] = [
        {
            title: 'Resumen',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Propiedades',
            href: panelIndex(),
            icon: House,
        },
        {
            title: 'Archivadas',
            href: archivedIndex(),
            icon: Archive,
        },
        {
            title: 'Consultas',
            href: inquiriesIndex(),
            icon: Inbox,
            badge: nav.inquiryCount,
        },
    ];

    const adminNavItems: NavItem[] = [
        {
            title: 'Crear agente',
            href: createAgent(),
            icon: Users,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader className="gap-0 p-0 px-4 pt-[29px] group-data-[collapsible=icon]:px-2">
                <Link
                    href={dashboard()}
                    prefetch
                    className="flex items-center gap-[9px] rounded-lg px-2 ring-sidebar-ring outline-hidden group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0 focus-visible:ring-2"
                >
                    <AppLogo />
                </Link>
                <SidebarWorkspace isAdmin={isAdmin} />
            </SidebarHeader>

            <SidebarContent className="gap-0">
                <NavMain label="Gestión" items={managementNavItems} />
                {isAdmin && (
                    <NavMain
                        label="Administración"
                        items={adminNavItems}
                        className="mt-[31px]"
                    />
                )}
            </SidebarContent>

            <SidebarFooter className="gap-0 p-0 px-4 pb-0 group-data-[collapsible=icon]:px-2">
                <SidebarContextNote />
                <div className="border-t border-sidebar-border">
                    <NavUser />
                </div>
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
