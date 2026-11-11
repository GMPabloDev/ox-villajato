import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 48 48"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M9 28C16 18 24 13 37 11"
                stroke="currentColor"
                strokeWidth="5.5"
                strokeLinecap="round"
            />
            <path
                d="M12 39C23 36 30 31 36 22"
                stroke="currentColor"
                strokeWidth="5.5"
                strokeLinecap="round"
            />
        </svg>
    );
}
