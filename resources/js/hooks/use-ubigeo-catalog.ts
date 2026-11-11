import { useCallback, useEffect, useState } from 'react';
import { index as ubigeoIndex } from '@/actions/App/Http/Controllers/UbigeoController';
import {
    isUbigeoCatalog,
    type UbigeoCatalog,
    UBIGEO_UNAVAILABLE_MESSAGE,
} from '@/lib/ubigeo';

export type UbigeoCatalogStatus = 'loading' | 'ready' | 'error';

export type UbigeoCatalogState = {
    status: UbigeoCatalogStatus;
    catalog: UbigeoCatalog | null;
    error: string | null;
    retry: () => void;
};

/**
 * Load the location catalog the property selectors need.
 *
 * A failed load keeps the catalog empty and exposes a retry, so the form never
 * offers a location it could not validate.
 */
export function useUbigeoCatalog(): UbigeoCatalogState {
    const [attempt, setAttempt] = useState(0);
    const [catalog, setCatalog] = useState<UbigeoCatalog | null>(null);
    const [status, setStatus] = useState<UbigeoCatalogStatus>('loading');
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        let isCurrent = true;

        setStatus('loading');
        setError(null);

        fetch(ubigeoIndex.url(), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(UBIGEO_UNAVAILABLE_MESSAGE);
                }

                return (await response.json()) as unknown;
            })
            .then((payload) => {
                if (!isCurrent) {
                    return;
                }

                if (!isUbigeoCatalog(payload)) {
                    setCatalog(null);
                    setError(UBIGEO_UNAVAILABLE_MESSAGE);
                    setStatus('error');

                    return;
                }

                setCatalog(payload);
                setError(null);
                setStatus('ready');
            })
            .catch(() => {
                if (!isCurrent) {
                    return;
                }

                setCatalog(null);
                setError(UBIGEO_UNAVAILABLE_MESSAGE);
                setStatus('error');
            });

        return () => {
            isCurrent = false;
            controller.abort();
        };
    }, [attempt]);

    const retry = useCallback(() => {
        setAttempt((value) => value + 1);
    }, []);

    return { status, catalog, error, retry };
}
