/**
 * Shows a sample card or certificate in a new tab using what is on the form right now
 * (including pictures just chosen). Nothing is saved. The tab is opened first, straight from the
 * click, so the phone does not block it as a pop-up.
 */
export async function openTryOut(url: string, body: FormData): Promise<string | null> {
    const tab = window.open('', '_blank');
    if (!tab) {
        return 'Your browser blocked the preview window. Allow pop-ups for this site and try again.';
    }
    tab.document.write('<meta name="viewport" content="width=device-width, initial-scale=1"><body style="font:16px system-ui;padding:24px;color:#334155">Getting your preview ready…</body>');

    try {
        const xsrf = document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='));
        const res = await fetch(url, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(xsrf ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrf.split('=')[1]) } : {}) },
        });
        const text = await res.text();

        if (!res.ok) {
            let message = 'The preview could not be made.';
            try {
                const json = JSON.parse(text);
                message = (Object.values(json.errors ?? {}).flat()[0] as string | undefined) ?? json.message ?? message;
            } catch { /* keep the plain message */ }
            tab.close();
            return message;
        }

        tab.document.open();
        tab.document.write(text);
        tab.document.close();
        return null;
    } catch {
        tab.close();
        return 'The preview could not be made. Check your connection and try again.';
    }
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
