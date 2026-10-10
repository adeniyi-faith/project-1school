import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';

export default function PageProgress() {
    const [visible, setVisible]     = useState(false);
    const [width, setWidth]         = useState(0);
    const [leaving, setLeaving]     = useState(false);
    const timerRef                  = useRef<ReturnType<typeof setTimeout> | null>(null);
    const incrementRef              = useRef<ReturnType<typeof setInterval> | null>(null);

    function clear() {
        if (timerRef.current)    clearTimeout(timerRef.current);
        if (incrementRef.current) clearInterval(incrementRef.current);
    }

    function start() {
        clear();
        setLeaving(false);
        setWidth(5);
        setVisible(true);

        // Slowly increment width to ~85% while loading
        let current = 5;
        incrementRef.current = setInterval(() => {
            // Decelerate as we approach 85
            const step = Math.max(0.5, (85 - current) * 0.06);
            current = Math.min(85, current + step);
            setWidth(current);
        }, 180);
    }

    function finish() {
        clear();
        setWidth(100);
        // Short pause at 100%, then fade out
        timerRef.current = setTimeout(() => {
            setLeaving(true);
            timerRef.current = setTimeout(() => {
                setVisible(false);
                setWidth(0);
                setLeaving(false);
            }, 300);
        }, 180);
    }

    useEffect(() => {
        const removeStart  = router.on('start',  start);
        const removeFinish = router.on('finish', finish);
        const removeError  = router.on('error',  finish);

        return () => {
            removeStart();
            removeFinish();
            removeError();
            clear();
        };
    }, []);

    if (!visible) return null;

    return (
        <div
            style={{
                position:   'fixed',
                top:        0,
                left:       0,
                width:      `${width}%`,
                height:     '3px',
                background: 'linear-gradient(90deg, #6366f1, #818cf8)',
                zIndex:     9999,
                transition: leaving
                    ? 'opacity 0.3s ease, width 0.1s ease'
                    : 'width 0.18s ease',
                opacity:    leaving ? 0 : 1,
                borderRadius: '0 2px 2px 0',
                boxShadow:  '0 0 8px rgba(99,102,241,0.6)',
            }}
        />
    );
}
