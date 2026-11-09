import { useCurrentUrl } from '@/hooks/use-current-url';
import { sidebarNoteFor } from '@/lib/sidebar-notes';

export function SidebarContextNote() {
    const { currentUrl } = useCurrentUrl();
    const note = sidebarNoteFor(currentUrl);

    if (note === null) {
        return null;
    }

    return (
        <div className="mx-2 mt-10 mb-6 border-t border-sidebar-border pt-[23px] group-data-[collapsible=icon]:hidden">
            <p className="text-[11px] leading-[1.9] text-muted-foreground">
                {note}
            </p>
        </div>
    );
}
