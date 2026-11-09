import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

export function NavMain({
    label,
    items,
    className,
}: {
    label: string;
    items: NavItem[];
    className?: string;
}) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarGroup
            className={cn(
                'gap-0 px-4 py-0 group-data-[collapsible=icon]:px-2',
                className,
            )}
        >
            <SidebarGroupLabel className="h-auto px-3 pb-2.5 text-[10px] leading-[1.2] font-semibold tracking-[1.5px] text-sidebar-foreground/70 uppercase">
                {label}
            </SidebarGroupLabel>
            <SidebarMenu className="gap-[5px]">
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            size="lg"
                            isActive={isCurrentOrParentUrl(item.href)}
                            tooltip={{ children: item.title }}
                            className="h-[43px] gap-3 rounded-[9px] px-[13px] text-[13px] text-sidebar-foreground/80 group-data-[collapsible=icon]:justify-center data-[active=true]:font-semibold data-[active=true]:text-sidebar-accent-foreground [&>svg]:size-[18px]"
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span className="group-data-[collapsible=icon]:hidden">
                                    {item.title}
                                </span>
                            </Link>
                        </SidebarMenuButton>
                        {item.badge ? (
                            <SidebarMenuBadge className="right-[13px] mt-px h-auto min-w-0 rounded-[5px] bg-card/60 px-1.5 py-[3px] text-[10px] font-medium">
                                {item.badge}
                            </SidebarMenuBadge>
                        ) : null}
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
