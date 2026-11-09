import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <AppLogoIcon className="size-8 shrink-0 text-primary" />
            <div className="grid flex-1 text-left leading-[1.2] group-data-[collapsible=icon]:hidden">
                <span className="font-display text-[24px] font-extrabold tracking-[-1px] text-foreground">
                    Villa<span className="text-primary">Jato</span>
                </span>
                <span className="mt-[3px] text-[9px] font-semibold tracking-[1.7px] text-muted-foreground uppercase">
                    Panel inmobiliario
                </span>
            </div>
        </>
    );
}
