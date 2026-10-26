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
        <div className="flex min-h-screen flex-col bg-neutral-50 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
            <header className="border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4">
                    <Link href={home()} className="flex items-center">
                        <AppLogo />
                    </Link>
                    <Link
                        href={auth.user ? dashboard() : login()}
                        className="text-sm font-medium text-neutral-600 transition-colors hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-white"
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
