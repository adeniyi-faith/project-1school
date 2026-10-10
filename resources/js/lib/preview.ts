/**
 * Shows a sample card or certificate in a new tab using what is on the form right now
 * (including pictures just chosen). Nothing is saved.
 *
 * The form is sent by the browser itself straight into the new tab, like a normal link. An earlier
 * version fetched the page with JavaScript and then wrote it into the tab, but phones pause the page
 * you left behind, so the tab sat on "getting ready" for ever.
 */
export function openTryOut(url: string, body: FormData): string | null {
    const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
    const name = `try-${Date.now()}`;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.enctype = 'multipart/form-data';
    form.target = name;
    form.style.display = 'none';

    const add = (key: string, value: string) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    };
    if (csrf) add('_token', csrf);

    for (const [key, value] of body.entries()) {
        if (typeof value === 'string') {
            add(key, value);
            continue;
        }
        if (value.size === 0) continue;
        const input = document.createElement('input');
        input.type = 'file';
        input.name = key;
        try {
            const files = new DataTransfer();
            files.items.add(value);
            input.files = files.files;
        } catch {
            return 'This browser cannot preview a picture that is not saved yet. Save it, then preview.';
        }
        form.appendChild(input);
    }

    const tab = window.open('', name);
    if (!tab) {
        return 'Your browser blocked the preview window. Allow pop-ups for this site and try again.';
    }
    tab.document.write('<meta name="viewport" content="width=device-width, initial-scale=1"><body style="font:16px system-ui;padding:24px;color:#334155">Getting your preview ready…</body>');

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => form.remove(), 1000);
    return null;
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
