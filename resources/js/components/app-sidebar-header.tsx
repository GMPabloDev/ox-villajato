import { usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { workspaceLabel } from '@/lib/workspace';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const isAdmin = usePage().props.auth.user.role === 'admin';

    return (
        <header className="flex h-14 shrink-0 items-center justify-between gap-2 border-b border-border bg-card/50 px-4 md:h-[66px] md:px-9">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger className="-ml-2 md:hidden" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <span className="hidden shrink-0 items-center gap-[7px] text-[11px] text-muted-foreground lg:flex">
                <span className="size-[5px] shrink-0 rounded-full bg-success" />
                {workspaceLabel(isAdmin)}
            </span>
        </header>
    );
}
