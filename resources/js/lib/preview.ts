import { useCallback, useRef, useState } from 'react';

export type PreviewState = { open: boolean; loading: boolean; html: string | null; error: string | null; title: string };

/**
 * A preview of a sample card or certificate made from what is on the form right now (including pictures
 * just chosen). Nothing is saved. It is fetched while the person stays on the page and shown over it,
 * so no new tab is opened and the form can never be reloaded or lost. (Opening a new tab did not work
 * well on phones: the tab stayed blank and the old page was reloaded.)
 */
export function usePreview() {
    const [state, setState] = useState<PreviewState>({ open: false, loading: false, html: null, error: null, title: 'Preview' });
    const run = useRef(0);

    const show = useCallback(async (url: string, body: FormData, title = 'Preview') => {
        const id = ++run.current;
        setState({ open: true, loading: true, html: null, error: null, title });

        const finish = (patch: Partial<PreviewState>) => {
            if (run.current === id) setState(s => ({ ...s, loading: false, ...patch }));
        };

        const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
        const abort = new AbortController();
        const timer = setTimeout(() => abort.abort(), 60000);
        try {
            const res = await fetch(url, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                signal: abort.signal,
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest', ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}) },
            });
            const text = await res.text();

            if (res.redirected) {
                // sent somewhere else (signed out, or a read-only account): that page is not a preview
                finish({ error: 'The preview was refused. If you are signed out, sign in again, then try again.' });
            } else if (res.status === 419) {
                finish({ error: 'This page has been open too long. Reload it, then try the preview again.' });
            } else if (res.status === 403) {
                finish({ error: 'You do not have permission to preview this.' });
            } else if (res.status === 413) {
                finish({ error: 'The pictures are too big to send. Try smaller ones.' });
            } else if (res.ok && text.includes('id="sheet"')) {
                finish({ html: text });
            } else if (res.status === 422 && text.includes('<h2')) {
                finish({ html: text }); // a plain page that says what to fix
            } else if (!res.ok) {
                finish({ error: `The preview could not be made (error ${res.status}). Please tell support.` });
            } else {
                // a page that is not a preview must never be shown inside the frame
                finish({ error: 'The server answered with something that is not a preview. Please tell support.' });
            }
        } catch (e) {
            finish({ error: (e as Error).name === 'AbortError' ? 'The preview took too long. Check your connection and try again.' : 'The preview could not be made. Check your connection and try again.' });
        } finally {
            clearTimeout(timer);
        }
    }, []);

    const close = useCallback(() => {
        run.current++;
        setState(s => ({ ...s, open: false }));
    }, []);

    return { state, show, close };
}

/** Adds a nested value (object, array or File) to a FormData the way Laravel reads it: a[b][0]=... */
export function appendDeep(form: FormData, key: string, value: unknown): void {
    if (value === null || value === undefined) return;
    if (value instanceof File) { form.append(key, value); return; }
    if (typeof value === 'boolean') { form.append(key, value ? '1' : '0'); return; }
    if (Array.isArray(value)) { value.forEach((v, i) => appendDeep(form, `${key}[${i}]`, v)); return; }
    if (typeof value === 'object') { Object.entries(value as Record<string, unknown>).forEach(([k, v]) => appendDeep(form, `${key}[${k}]`, v)); return; }
    form.append(key, String(value));
}
