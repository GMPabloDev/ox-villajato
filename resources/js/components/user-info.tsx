import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import type { User } from '@/types';

const roleLabels: Record<User['role'], string> = {
    admin: 'Administrador',
    agent: 'Agente',
};

export function UserInfo({
    user,
    showEmail = false,
    showRole = false,
    compact = false,
}: {
    user: User;
    showEmail?: boolean;
    showRole?: boolean;
    compact?: boolean;
}) {
    const getInitials = useInitials();

    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                <AvatarImage src={user.avatar} alt={user.name} />
                <AvatarFallback className="rounded-lg bg-sidebar-accent text-sidebar-accent-foreground">
                    {getInitials(user.name)}
                </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span
                    className={cn(
                        'truncate',
                        compact ? 'text-[11px] font-semibold' : 'font-medium',
                    )}
                >
                    {user.name}
                </span>
                {showEmail && (
                    <span className="truncate text-xs text-muted-foreground">
                        {user.email}
                    </span>
                )}
                {showRole && (
                    <span
                        className={cn(
                            'truncate text-muted-foreground',
                            compact ? 'mt-[5px] text-[10px]' : 'text-xs',
                        )}
                    >
                        {roleLabels[user.role]}
                    </span>
                )}
            </div>
        </>
    );
}
