import { useEffect, useState } from 'react';
import axios from 'axios';
import { Link } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';

interface Source { key: string; kind: 'scheme' | 'syllabus'; label: string; link: string; items: { label: string; topic: string; taught: boolean }[] }

/**
 * "What does this exam cover?": the topics of the class and subject's scheme of work (and syllabus) with the ones already
 * taught ticked. The person ticks or unticks, and the chosen topics are handed back so the exam can be set from them.
 */
export default function TaughtScope({ classId, subjectId, termId, value, onChange, disabled }: {
    classId: number | null; subjectId: number | null; termId: number | null; value: string[]; onChange: (topics: string[]) => void; disabled?: boolean;
}) {
    const [sources, setSources] = useState<Source[] | null>(null);
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        if (!classId || !subjectId) { setSources(null); return; }
        let live = true;
        setFailed(false);
        axios.get<{ sources: Source[] }>('/school/cbt/taught', { params: { class_id: classId, subject_id: subjectId, term_id: termId ?? undefined } })
            .then(r => { if (live) setSources(r.data.sources); })
            .catch(() => { if (live) { setSources([]); setFailed(true); } });
        return () => { live = false; };
    }, [classId, subjectId, termId]);

    const chosen = new Set(value.map(v => v.toLowerCase()));
    const all = sources?.flatMap(s => s.items) ?? [];
    const taught = [...new Set(all.filter(i => i.taught).map(i => i.topic))];

    function toggle(topic: string, on: boolean) {
        const rest = value.filter(v => v.toLowerCase() !== topic.toLowerCase());
        onChange(on ? [...rest, topic] : rest);
    }

    if (!classId || !subjectId) return <p className="text-sm text-slate-500">Choose the class and subject above to see the topics taught.</p>;
    if (sources === null) return <p className="flex items-center gap-2 text-sm text-slate-500"><Loader2 className="size-4 animate-spin" /> Looking up the scheme of work…</p>;
    if (sources.length === 0) {
        return (
            <p className="text-sm text-slate-500">
                {failed ? 'The topics could not be loaded just now.' : 'No scheme of work or syllabus is saved for this class and subject yet.'}{' '}
                <Link href="/school/lesson-notes/schemes" className="text-indigo-600 underline">Add a scheme of work</Link>
            </p>
        );
    }

    return (
        <div className="space-y-3">
            <div className="flex flex-wrap items-center gap-2 text-sm">
                <span><b>{value.length}</b> topic{value.length === 1 ? '' : 's'} in the exam scope</span>
                {!disabled && (
                    <>
                        <Button type="button" size="sm" variant="outline" disabled={taught.length === 0} onClick={() => onChange(taught)}>Use only what was taught ({taught.length})</Button>
                        <Button type="button" size="sm" variant="ghost" disabled={value.length === 0} onClick={() => onChange([])}>Clear</Button>
                    </>
                )}
            </div>
            {taught.length === 0 && <p className="text-xs text-amber-700 dark:text-amber-300">Nothing is marked as taught yet. Open the scheme of work and tick the weeks you have taught, or tick topics here by hand.</p>}
            {sources.map(src => (
                <div key={src.key} className="rounded-lg border border-slate-200 dark:border-white/10">
                    <div className="flex items-center justify-between gap-2 border-b border-slate-100 px-3 py-2 text-sm font-medium dark:border-white/[0.06]">
                        {src.label}
                        <Link href={src.link} className="text-xs font-normal text-indigo-600 hover:underline">Open</Link>
                    </div>
                    <ul className="grid gap-x-4 sm:grid-cols-2">
                        {src.items.map((it, n) => (
                            <li key={n}>
                                <label className="flex items-start gap-2 px-3 py-1.5 text-sm">
                                    <Checkbox className="mt-0.5" disabled={disabled} checked={chosen.has(it.topic.toLowerCase())} onCheckedChange={v => toggle(it.topic, !!v)} />
                                    <span className="min-w-0 flex-1">
                                        {it.label && <span className="mr-1 text-xs text-slate-500">{it.label}</span>}{it.topic}
                                        {it.taught ? <span className="ml-1.5 text-xs text-emerald-700 dark:text-emerald-300">taught</span> : <span className="ml-1.5 text-xs text-slate-400">not taught yet</span>}
                                    </span>
                                </label>
                            </li>
                        ))}
                    </ul>
                </div>
            ))}
        </div>
    );
}
