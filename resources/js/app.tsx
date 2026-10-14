import '../css/app.css';
import { Component, type ReactNode } from 'react';
import { createInertiaApp, http } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/components/ui/sonner';

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
