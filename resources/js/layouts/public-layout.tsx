import { Link, usePage } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { dashboard, home, login } from '@/routes';

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { auth } = usePage().props;

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <header className="border-b border-border bg-card">
                <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4">
                    <Link href={home()} className="flex items-center">
                        <AppLogo />
                    </Link>
                    <Link
                        href={auth.user ? dashboard() : login()}
                        className="text-sm font-medium text-muted-foreground transition-colors hover:text-primary"
                    >
                        {auth.user ? 'Panel' : 'Ingresar'}
                    </Link>
                </div>
            </header>
            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
                {children}
            </main>
        </div>
    );
}
