import { useEffect } from 'react';
import { Loader2, X } from 'lucide-react';
import type { PreviewState } from '@/lib/preview';

/** The finished preview, shown over the page. While it is being prepared a message shows instead. */
export function PreviewOverlay({ state, onClose }: { state: PreviewState; onClose: () => void }) {
    useEffect(() => {
        if (!state.open) return;
        const keys = (e: KeyboardEvent) => e.key === 'Escape' && onClose();
        const before = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        addEventListener('keydown', keys);
        return () => { document.body.style.overflow = before; removeEventListener('keydown', keys); };
    }, [state.open, onClose]);

    if (!state.open) return null;

    return (
        <div className="fixed inset-0 z-[100] flex flex-col bg-slate-900" role="dialog" aria-modal="true" aria-label={state.title}>
            <div className="flex items-center justify-between gap-3 bg-slate-900 px-3 py-2 text-white">
                <span className="truncate text-sm font-semibold">{state.title}</span>
                <button type="button" onClick={onClose} className="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-2 text-sm font-medium hover:bg-white/20">
                    <X className="size-4" /> Close
                </button>
            </div>
            <div className="relative min-h-0 flex-1 bg-slate-200">
                {state.loading && (
                    <div className="absolute inset-0 flex flex-col items-center justify-center gap-3 text-slate-600">
                        <Loader2 className="size-8 animate-spin" />
                        <p className="text-sm">Getting your preview ready…</p>
                    </div>
                )}
                {state.error && (
                    <div className="mx-auto max-w-md p-6 text-center">
                        <p className="mb-1 font-semibold text-red-700">The preview could not be made</p>
                        <p className="text-sm text-slate-700">{state.error}</p>
                        <button type="button" onClick={onClose} className="mt-4 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Close and go back</button>
                    </div>
                )}
                {state.html && <iframe title={state.title} srcDoc={state.html} className="size-full border-0 bg-white" />}
            </div>
        </div>
    );
}
