import type { Auth } from '@/types/auth';
import type { NavCounts } from '@/types/navigation';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            nav: NavCounts;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
