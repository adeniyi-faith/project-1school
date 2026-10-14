import '../css/app.css';
import { Component, type ReactNode } from 'react';
import { createInertiaApp, http, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'sonner';

class ErrorBoundary extends Component<{ children: ReactNode }, { error: Error | null }> {
    state = { error: null };
    static getDerivedStateFromError(error: Error) { return { error }; }
    render() {
        if (this.state.error) {
            return (
                <div style={{ padding: '2rem', fontFamily: 'monospace' }}>
                    <h2 style={{ color: 'red' }}>Runtime Error</h2>
                    <pre style={{ whiteSpace: 'pre-wrap' }}>{String(this.state.error)}</pre>
                </div>
            );
        }
        return this.props.children;
    }
}

const appName = document.title;

// The live web server answers every PUT, PATCH and DELETE request with an empty error (Inertia shows it as a blank
// white pop-up) but accepts POST. So send those saves as a POST that says which method it really is; Laravel then
// treats it as the original method.
http.onRequest((config) => {
    const method = String(config.method || '').toLowerCase();
    if (method !== 'put' && method !== 'patch' && method !== 'delete') return config;

    const headers = { ...(config.headers ?? {}), 'X-HTTP-Method-Override': method.toUpperCase() };
    let data = config.data;
    if (data instanceof FormData) data.append('_method', method.toUpperCase());
    else if (data && typeof data === 'object') data = { ...data, _method: method.toUpperCase() };
    else if (data === null || data === undefined) data = { _method: method.toUpperCase() };

    return { ...config, method: 'post', headers, data };
});

// If the web server itself refuses a request (before the app can answer), Inertia would draw the server's raw page
// in a blank white pop-up. Show a short plain message instead and leave the person on their page.
router.on('httpException', (event) => {
    event.preventDefault();
    const { status, data } = event.detail.response;
    // A short plain-text peek at what the server answered, so support can tell what went wrong
    const peek = String(data ?? '').replace(/<style[\s\S]*?<\/style>|<script[\s\S]*?<\/script>|<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 140);
    toast.error(status === 413
        ? 'That file is too big to upload. Please choose a smaller one.'
        : `The server refused this request (code ${status}). Nothing was saved. Please try again, or use a smaller file.${peek ? ` Server said: ${peek}` : ''}`);
});
router.on('networkError', (event) => {
    event.preventDefault();
    toast.error('We could not reach the server. Check your internet connection and try again.');
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(
            <ErrorBoundary>
                <App {...props} />
                <Toaster richColors position="top-right" />
            </ErrorBoundary>,
        );
    },
    progress: {
        color: '#6366f1',
    },
});
