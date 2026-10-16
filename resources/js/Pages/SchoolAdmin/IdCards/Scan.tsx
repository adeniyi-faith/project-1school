import { useCallback, useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import jsQR from 'jsqr';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PageHeader, Panel, Pill, Segmented } from '@/components/app/kit';
import { Camera, CameraOff, Loader2, ScanLine } from 'lucide-react';

import { naira } from '@/lib/format';
type CardState = 'valid' | 'expired' | 'replaced' | 'revoked';
interface ScanResult {
    found: boolean; message?: string;
    card?: { card_no: string; state: CardState; expires_on: string | null; note: string | null };
    student?: {
        id: number; name: string; admission_no: string; status: string; class: string;
        house: { name: string; colour: string } | null; photo_url: string | null;
        guardian: { name: string; phone: string | null } | null;
    };
    attendance?: string | null;
    fees?: { owed: number; open_invoices: number; overdue: number } | null;
    marked?: 'marked' | 'already' | 'refused' | null;
}
interface Props { code: string | null; can: { attendance: boolean; fees: boolean } }
type Mode = 'look' | 'present' | 'late';

const STATE_TEXT: Record<CardState, { label: string; tone: 'good' | 'warn' | 'bad' }> = {
    valid: { label: 'Card is valid', tone: 'good' }, expired: { label: 'Card has expired', tone: 'warn' },
    replaced: { label: 'Old card: a newer one was made', tone: 'bad' }, revoked: { label: 'Card was stopped', tone: 'bad' },
};

export default function ScanIdCard({ code, can }: Props) {
    const [mode, setMode] = useState<Mode>(can.attendance ? 'present' : 'look');
    const [typed, setTyped] = useState('');
    const [busy, setBusy] = useState(false);
    const [result, setResult] = useState<ScanResult | null>(null);
    const [log, setLog] = useState<{ at: string; name: string; outcome: string }[]>([]);
    const [camera, setCamera] = useState<'off' | 'starting' | 'on' | 'error'>('off');
    const [cameraError, setCameraError] = useState('');
    const video = useRef<HTMLVideoElement>(null);
    const canvas = useRef<HTMLCanvasElement>(null);
    const stream = useRef<MediaStream | null>(null);
    const last = useRef<{ code: string; at: number }>({ code: '', at: 0 });
    const modeRef = useRef(mode);
    modeRef.current = mode;
    const input = useRef<HTMLInputElement>(null);

    const lookup = useCallback(async (value: string) => {
        const clean = value.trim();
        if (!clean) return;
        setBusy(true);
        try {
            const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
            const res = await fetch('/school/id-cards/lookup', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ code: clean, mark: modeRef.current === 'look' ? null : modeRef.current }),
            });
            const data: ScanResult = res.status === 419
                ? { found: false, message: 'This page has been open too long. Reload it, then scan again.' }
                : res.status === 429 ? { found: false, message: 'Too many scans in a minute. Wait a moment.' }
                : await res.json().catch(() => ({ found: false, message: 'Something went wrong. Try again.' }));
            setResult(data);
            if ('vibrate' in navigator) navigator.vibrate(data.found && data.card?.state === 'valid' ? 80 : [80, 60, 80]);
            setLog(l => [{
                at: new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }),
                name: data.student?.name ?? 'Unknown card',
                outcome: !data.found ? 'Not found' : data.marked === 'marked' ? `Marked ${modeRef.current}` : data.marked === 'already' ? 'Already marked today' : data.marked === 'refused' ? 'Not marked' : STATE_TEXT[data.card!.state].label,
            }, ...l].slice(0, 12));
        } catch {
            setResult({ found: false, message: 'Could not reach the school system. Check the internet connection.' });
        } finally {
            setBusy(false);
            setTyped('');
            input.current?.focus();
        }
    }, []);

    // A card opened from its QR link (/id/...) lands here with the code filled in
    useEffect(() => { if (code) void lookup(code); }, [code, lookup]);

    const stopCamera = useCallback(() => {
        stream.current?.getTracks().forEach(t => t.stop());
        stream.current = null;
        setCamera('off');
    }, []);
    useEffect(() => stopCamera, [stopCamera]);

    async function startCamera() {
        setCameraError('');
        if (!navigator.mediaDevices?.getUserMedia) {
            setCamera('error');
            setCameraError('This browser cannot use the camera here. Use a phone or tablet browser such as Chrome, or a USB scanner.');
            return;
        }
        setCamera('starting');
        try {
            stream.current = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            if (video.current) {
                video.current.srcObject = stream.current;
                await video.current.play();
            }
            setCamera('on');
        } catch {
            setCamera('error');
            setCameraError('The camera could not start. Allow camera access for this site in the browser, then try again.');
        }
    }

    // Read the camera picture a few times a second and look for a QR code
    useEffect(() => {
        if (camera !== 'on') return;
        let stopped = false;
        const tick = () => {
            if (stopped) return;
            const v = video.current, c = canvas.current;
            if (v && c && v.readyState >= 2 && v.videoWidth) {
                const scale = Math.min(1, 640 / v.videoWidth);
                c.width = Math.round(v.videoWidth * scale);
                c.height = Math.round(v.videoHeight * scale);
                const ctx = c.getContext('2d', { willReadFrequently: true });
                if (ctx) {
                    ctx.drawImage(v, 0, 0, c.width, c.height);
                    const found = jsQR(ctx.getImageData(0, 0, c.width, c.height).data, c.width, c.height, { inversionAttempts: 'dontInvert' });
                    const now = Date.now();
                    // the same card held in front of the camera is read once every few seconds, not over and over
                    if (found?.data && (found.data !== last.current.code || now - last.current.at > 4000)) {
                        last.current = { code: found.data, at: now };
                        void lookup(found.data);
                    }
                }
            }
            setTimeout(tick, 250);
        };
        tick();
        return () => { stopped = true; };
    }, [camera, lookup]);

    const modes = [
        { value: 'look' as const, label: 'Just check' },
        ...(can.attendance ? [{ value: 'present' as const, label: 'Mark present' }, { value: 'late' as const, label: 'Mark late' }] : []),
    ];

    return (
        <AppLayout title="Scan ID card">
            <div className="space-y-6">
                <PageHeader title="Scan ID card" description="Point the camera at a card's QR code, use a USB scanner, or type the card number. You see who the student is, whether the card is valid, today's attendance and fees owed." />

                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="space-y-4">
                        {modes.length > 1 && (
                            <div>
                                <Segmented label="What happens on scan" value={mode} onChange={setMode} options={modes} />
                                {mode !== 'look' && <p className="mt-1 text-xs text-slate-500">Each valid card scanned is marked {mode} for today. A student already marked today is left as they are.</p>}
                            </div>
                        )}
                        <Panel>
                            <div className="relative overflow-hidden rounded-lg bg-slate-900" style={{ aspectRatio: '4 / 3' }}>
                                <video ref={video} playsInline muted className={`size-full object-cover ${camera === 'on' ? '' : 'hidden'}`} />
                                {camera === 'on' && <div className="pointer-events-none absolute inset-[18%] rounded-xl border-2 border-white/70" />}
                                {camera !== 'on' && (
                                    <div className="absolute inset-0 flex flex-col items-center justify-center gap-3 p-4 text-center text-slate-300">
                                        {camera === 'starting' ? <Loader2 className="size-8 animate-spin" /> : <ScanLine className="size-10" />}
                                        <p className="text-sm">{camera === 'error' ? cameraError : 'The camera is off.'}</p>
                                    </div>
                                )}
                                <canvas ref={canvas} className="hidden" />
                            </div>
                            <div className="mt-3 flex flex-wrap gap-2">
                                {camera === 'on'
                                    ? <Button variant="outline" onClick={stopCamera}><CameraOff className="size-4" /> Stop camera</Button>
                                    : <Button onClick={startCamera} disabled={camera === 'starting'} className="bg-indigo-600 text-white hover:bg-indigo-700"><Camera className="size-4" /> Start camera</Button>}
                            </div>
                            <form onSubmit={e => { e.preventDefault(); void lookup(typed); }} className="mt-4 flex gap-2">
                                <Input ref={input} autoFocus value={typed} onChange={e => setTyped(e.target.value)} placeholder="Card number (e.g. ID/2026/0001) or scanner" aria-label="Card number" />
                                <Button type="submit" variant="outline" disabled={busy || !typed.trim()}>Check</Button>
                            </form>
                        </Panel>
                    </div>

                    <div className="space-y-4">
                        <ResultCard result={result} busy={busy} mode={mode} showFees={can.fees} />
                        {log.length > 0 && (
                            <Panel title="Scanned just now" flush>
                                <ul className="divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                                    {log.map((l, i) => (
                                        <li key={i} className="flex items-center gap-3 px-4 py-2">
                                            <span className="w-16 shrink-0 text-xs text-slate-500">{l.at}</span>
                                            <span className="min-w-0 flex-1 truncate">{l.name}</span>
                                            <span className="text-xs text-slate-500">{l.outcome}</span>
                                        </li>
                                    ))}
                                </ul>
                            </Panel>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function ResultCard({ result, busy, mode, showFees }: { result: ScanResult | null; busy: boolean; mode: Mode; showFees: boolean }) {
    if (busy && !result) return <Panel><p className="flex items-center gap-2 text-sm text-slate-500"><Loader2 className="size-4 animate-spin" /> Checking…</p></Panel>;
    if (!result) return <Panel><p className="text-sm text-slate-500">Scan a card to see the student here.</p></Panel>;
    if (!result.found || !result.student || !result.card) {
        return <div className="rounded-xl border-2 border-red-300 bg-red-50 p-4 text-red-800 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-300"><p className="font-semibold">Not found</p><p className="text-sm">{result.message ?? 'No card matches that code.'}</p></div>;
    }
    const { student: s, card } = result;
    const state = STATE_TEXT[card.state];
    const border = state.tone === 'good' ? 'border-emerald-400' : state.tone === 'warn' ? 'border-amber-400' : 'border-red-400';

    return (
        <div className={`rounded-xl border-2 bg-white p-4 dark:bg-slate-900 ${border}`}>
            <div className="flex gap-4">
                <div className="h-28 w-24 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800">
                    {s.photo_url ? <img src={s.photo_url} alt={`Photo of ${s.name}`} className="size-full object-cover" /> : <div className="flex size-full items-center justify-center text-2xl font-bold text-slate-400">{s.name[0]}</div>}
                </div>
                <div className="min-w-0 flex-1 space-y-1">
                    <Link href={`/school/students/${s.id}`} className="block text-lg font-semibold hover:underline">{s.name}</Link>
                    <p className="text-sm text-slate-500">{s.admission_no}{s.class ? ` · ${s.class}` : ''}</p>
                    {s.house && <p className="flex items-center gap-1.5 text-sm"><span className="size-2.5 rounded-full" style={{ background: s.house.colour }} /> {s.house.name}</p>}
                    <div className="flex flex-wrap gap-1.5 pt-1">
                        <Pill tone={state.tone}>{state.label}</Pill>
                        {s.status !== 'active' && <Pill tone="bad">Student is {s.status}</Pill>}
                    </div>
                </div>
            </div>
            <dl className="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt className="text-xs text-slate-500">Today</dt>
                    <dd className="font-medium">
                        {result.marked === 'marked' ? <span className="text-emerald-600">Marked {mode} now</span>
                            : result.marked === 'already' ? <>Already marked: {result.attendance}</>
                            : result.marked === 'refused' ? <span className="text-red-600">Not marked (card or student not valid)</span>
                            : result.attendance ? <span className="capitalize">{result.attendance}</span> : 'Not marked yet'}
                    </dd>
                </div>
                <div>
                    <dt className="text-xs text-slate-500">Card</dt>
                    <dd className="font-medium">{card.card_no}{card.expires_on ? <span className="block text-xs font-normal text-slate-500">valid until {card.expires_on}</span> : null}</dd>
                </div>
                {showFees && result.fees && (
                    <div className="col-span-2 rounded-lg bg-slate-50 p-3 dark:bg-white/5">
                        <dt className="text-xs text-slate-500">Fees</dt>
                        <dd className="font-medium">
                            {result.fees.owed > 0
                                ? <><span className="text-amber-700 dark:text-amber-400">{naira(result.fees.owed)} owed</span> on {result.fees.open_invoices} invoice{result.fees.open_invoices === 1 ? '' : 's'}{result.fees.overdue > 0 && <span className="block text-sm text-red-600">{naira(result.fees.overdue)} is past its due date</span>}</>
                                : <span className="text-emerald-600">Nothing owed</span>}
                        </dd>
                    </div>
                )}
                {s.guardian && (
                    <div className="col-span-2">
                        <dt className="text-xs text-slate-500">Parent or guardian</dt>
                        <dd className="font-medium">{s.guardian.name}{s.guardian.phone && <> · <a href={`tel:${s.guardian.phone}`} className="text-indigo-600 hover:underline">{s.guardian.phone}</a></>}</dd>
                    </div>
                )}
            </dl>
        </div>
    );
}
