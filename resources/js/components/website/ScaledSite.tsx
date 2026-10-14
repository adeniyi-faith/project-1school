import { useEffect, useRef, useState } from 'react';

/**
 * A real web page shown small, like a screenshot: the page is drawn at full size (a laptop or a phone width)
 * inside a frame, then shrunk to fit the box. It cannot be clicked; it is only a picture of the site.
 */
export function ScaledSite({ src, title, width, height, className = '' }: { src: string; title: string; width: number; height: number; className?: string }) {
    const box = useRef<HTMLDivElement>(null);
    const [scale, setScale] = useState(0);
    const [loaded, setLoaded] = useState(false);

    useEffect(() => {
        const el = box.current;
        if (!el) return;
        const fit = () => setScale(el.clientWidth / width);
        fit();
        const watch = new ResizeObserver(fit);
        watch.observe(el);
        return () => watch.disconnect();
    }, [width]);

    useEffect(() => setLoaded(false), [src]);

    return (
        <div ref={box} className={`relative w-full overflow-hidden bg-slate-100 dark:bg-white/5 ${className}`} style={{ height }} aria-hidden>
            {!loaded && <div className="absolute inset-0 animate-pulse bg-gradient-to-b from-slate-100 to-slate-200 dark:from-white/5 dark:to-white/10" />}
            {scale > 0 && (
                <iframe title={title} src={src} loading="lazy" tabIndex={-1} onLoad={() => setLoaded(true)}
                    className="pointer-events-none absolute left-0 top-0 origin-top-left border-0 bg-white"
                    style={{ width, height: height / scale, transform: `scale(${scale})`, opacity: loaded ? 1 : 0, transition: 'opacity .3s' }} />
            )}
        </div>
    );
}
