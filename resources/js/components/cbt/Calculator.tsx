import { useState } from 'react';
import { X } from 'lucide-react';

type Op = '+' | '−' | '×' | '÷';

/** 0.1 + 0.2 shows as 0.3, and very long numbers stay readable */
function tidy(n: number): string {
    if (!Number.isFinite(n)) return 'Error';
    return String(parseFloat(n.toPrecision(12)));
}

function work(a: number, op: Op, b: number): number {
    switch (op) {
        case '+': return a + b;
        case '−': return a - b;
        case '×': return a * b;
        case '÷': return b === 0 ? NaN : a / b;
    }
}

/** A simple on-screen calculator for tests that allow one: + − × ÷, square root, percent and change sign. Works out each step as it goes, like a handheld one. */
export default function Calculator({ onClose }: { onClose: () => void }) {
    const [shown, setShown] = useState('0');
    const [stored, setStored] = useState<number | null>(null);
    const [op, setOp] = useState<Op | null>(null);
    // fresh: the next digit starts a new number. typed: a second number has been given since the operator.
    const [fresh, setFresh] = useState(true);
    const [typed, setTyped] = useState(false);

    const value = () => (shown === 'Error' ? 0 : parseFloat(shown));

    function digit(d: string) {
        setTyped(true);
        if (fresh || shown === '0' || shown === 'Error') {
            setShown(d === '.' ? '0.' : d);
            setFresh(false);
            return;
        }
        if (d === '.' && shown.includes('.')) return;
        if (shown.replace(/[-.]/g, '').length >= 14) return;
        setShown(shown + d);
    }

    function operator(next: Op) {
        if (op && stored !== null && typed) {
            const result = work(stored, op, value());
            setShown(tidy(result));
            setStored(Number.isFinite(result) ? result : null);
        } else {
            setStored(value());
        }
        setOp(next);
        setFresh(true);
        setTyped(false);
    }

    function equals() {
        if (!op || stored === null) return;
        setShown(tidy(work(stored, op, value())));
        setStored(null);
        setOp(null);
        setFresh(true);
        setTyped(false);
    }

    function clear() {
        setShown('0');
        setStored(null);
        setOp(null);
        setFresh(true);
        setTyped(false);
    }

    function back() {
        if (fresh || shown === 'Error') return;
        setShown(shown.length > 1 && !(shown.length === 2 && shown.startsWith('-')) ? shown.slice(0, -1) : '0');
    }

    function single(f: (n: number) => number) {
        setShown(tidy(f(value())));
        setFresh(true);
        setTyped(true);
    }

    const key = 'h-11 rounded-lg text-base font-medium transition-colors active:scale-95';
    const plain = `${key} bg-white text-slate-900 hover:bg-slate-100 dark:bg-slate-800 dark:text-white dark:hover:bg-slate-700`;
    const side = `${key} bg-slate-200 text-slate-800 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600`;
    const main = `${key} bg-indigo-600 text-white hover:bg-indigo-700`;
    const opKey = (o: Op) => <button type="button" onClick={() => operator(o)} className={`${side} ${op === o && fresh ? 'ring-2 ring-indigo-500' : ''}`} aria-label={{ '+': 'Add', '−': 'Subtract', '×': 'Multiply', '÷': 'Divide' }[o]}>{o}</button>;
    const num = (d: string) => <button type="button" onClick={() => digit(d)} className={plain}>{d}</button>;

    return (
        <div role="dialog" aria-label="Calculator" className="fixed bottom-4 right-4 z-30 w-64 rounded-2xl border border-slate-200 bg-slate-100 p-3 shadow-xl dark:border-white/10 dark:bg-slate-900">
            <div className="mb-2 flex items-center justify-between">
                <span className="text-xs font-medium text-slate-500">Calculator</span>
                <button type="button" onClick={onClose} aria-label="Close the calculator" className="rounded p-1 text-slate-500 hover:bg-slate-200 dark:hover:bg-white/10"><X className="size-4" /></button>
            </div>
            <div className="mb-2 rounded-lg bg-white px-3 py-2 text-right dark:bg-slate-950">
                <p className="h-4 text-xs text-slate-400 tabular-nums">{stored !== null && op ? `${tidy(stored)} ${op}` : ''}</p>
                <p aria-live="polite" className="truncate font-mono text-2xl tabular-nums text-slate-900 dark:text-white">{shown}</p>
            </div>
            <div className="grid grid-cols-4 gap-1.5">
                <button type="button" onClick={clear} className={side}>C</button>
                <button type="button" onClick={back} className={side} aria-label="Delete last digit">⌫</button>
                <button type="button" onClick={() => single(n => n / 100)} className={side}>%</button>
                {opKey('÷')}
                {num('7')}{num('8')}{num('9')}{opKey('×')}
                {num('4')}{num('5')}{num('6')}{opKey('−')}
                {num('1')}{num('2')}{num('3')}{opKey('+')}
                <button type="button" onClick={() => single(n => -n)} className={plain} aria-label="Change sign">±</button>
                {num('0')}
                <button type="button" onClick={() => digit('.')} className={plain}>.</button>
                <button type="button" onClick={equals} className={main}>=</button>
                <button type="button" onClick={() => single(n => (n < 0 ? NaN : Math.sqrt(n)))} className={`${side} col-span-2`} aria-label="Square root">√ square root</button>
                <button type="button" onClick={() => single(n => n * n)} className={`${side} col-span-2`} aria-label="Square">x² square</button>
            </div>
        </div>
    );
}
