import { Building2 } from 'lucide-react';
import { workspaceLabel } from '@/lib/workspace';

export function SidebarWorkspace({ isAdmin }: { isAdmin: boolean }) {
    return (
        <div className="mx-0.5 mt-[33px] mb-[30px] flex items-center gap-[9px] rounded-[11px] border border-sidebar-border bg-card/60 px-[9px] py-3 group-data-[collapsible=icon]:hidden">
            <span className="flex size-[30px] shrink-0 items-center justify-center rounded-[7px] bg-sidebar-accent/70 text-sidebar-accent-foreground">
                <Building2 className="size-[18px]" />
            </span>
            <div className="min-w-0">
                <p className="truncate text-[12px] font-semibold text-foreground">
                    Mi Inmobiliaria
                </p>
                <p className="mt-[5px] text-[10px] leading-[1.5] text-muted-foreground">
                    {workspaceLabel(isAdmin)}
                </p>
            </div>
        </div>
    );
}
