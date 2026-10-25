import { useState } from 'react';
import axios from 'axios';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Pill, Segmented } from '@/components/app/kit';
import { Loader2, Sparkles } from 'lucide-react';
import type { CbtDifficulty, CbtQuestionType } from '@/Types';

export interface AiWriterInfo {
    shown: boolean;
    reason: string | null;
    /** The bank's class, used as the level unless the teacher types another */
    level: string | null;
    /** Topics from the subject's schemes of work */
    topics: string[];
    styles?: { key: string; label: string; hint: string }[];
    default_style?: string;
}

interface Draft {
    type: CbtQuestionType;
    text: string;
    options: string[];
    correct_option: number | null;
    marking_guide: string | null;
    explanation: string | null;
    topic: string | null;
    difficulty: CbtDifficulty;
    marks: number;
    keep: boolean;
}

const LETTERS = 'ABCDEF';
const LEVELS = [{ value: 'mixed', label: 'A mix' }, { value: 'easy', label: 'Easy' }, { value: 'medium', label: 'Medium' }, { value: 'hard', label: 'Hard' }];
const PARTS = [{ value: '0', label: 'No parts' }, { value: '2', label: '(a) and (b)' }, { value: '3', label: '(a) to (c)' }, { value: '4', label: '(a) to (d)' }];

/** Ask the AI for questions on a topic, check and change them, then add the ones worth keeping to the bank */
export default function AiQuestionWriter({ url, info, topics, onClose }: { url: string; info: AiWriterInfo; topics: string[]; onClose: () => void }) {
    const [ask, setAsk] = useState({ type: 'objective' as CbtQuestionType, count: '10', topic: '', difficulty: 'mixed', level: info.level ?? '', parts: '3', marks: '10', instructions: '', style: info.default_style ?? '' });
    const [drafts, setDrafts] = useState<Draft[]>([]);
    const [busy, setBusy] = useState(false);
    const [saving, setSaving] = useState(false);
    const [note, setNote] = useState<{ tone: 'good' | 'bad'; text: string } | null>(null);
    const [stage, setStage] = useState<'ask' | 'review'>('ask');
    const suggestions = [...new Set([...info.topics, ...topics])];
    const theory = ask.type === 'theory';
    const kept = drafts.filter(d => d.keep);

    async function write(e: React.FormEvent) {
        e.preventDefault();
        setBusy(true);
        setNote(null);
        try {
            const { data } = await axios.post<{ questions: Omit<Draft, 'keep'>[] }>(`${url}/ai`, {
                type: ask.type, count: Number(ask.count), topic: ask.topic, difficulty: ask.difficulty, level: ask.level || null,
                parts: theory ? Number(ask.parts) : null, marks: theory ? Number(ask.marks) : null, instructions: ask.instructions || null, style: ask.style || null,
            });
            setDrafts(prev => [...prev, ...data.questions.map(q => ({ ...q, keep: true }))]);
            setStage('review');
            setNote({ tone: 'good', text: `The AI wrote ${data.questions.length} question${data.questions.length === 1 ? '' : 's'}. Check every one: the answer, the wording and the level. Untick any you do not want.` });
        } catch (err) {
            const errs = axios.isAxiosError(err) ? err.response?.data?.errors : null;
            const first = errs ? (Object.values(errs)[0] as string[] | undefined)?.[0] : null;
            setNote({ tone: 'bad', text: first || 'The AI could not write questions just now. Try again.' });
        } finally {
            setBusy(false);
        }
    }

    function change(i: number, patch: Partial<Draft>) {
        setDrafts(list => list.map((d, n) => (n === i ? { ...d, ...patch } : d)));
    }

    function save() {
        setSaving(true);
        router.post(`${url}/questions/many`, {
            questions: kept.map(({ keep: _keep, ...q }) => ({ ...q, options: q.type === 'theory' ? [] : q.options })),
        }, {
            preserveScroll: true,
            onSuccess: () => onClose(),
            onError: errs => setNote({ tone: 'bad', text: Object.values(errs)[0] ?? 'Some questions could not be saved.' }),
            onFinish: () => setSaving(false),
        });
    }

    const noteBox = note && (
        <p role="status" className={`rounded-lg px-3 py-2 text-sm ${note.tone === 'good' ? 'bg-violet-50 text-violet-900 dark:bg-violet-500/10 dark:text-violet-200' : 'bg-red-50 text-red-800 dark:bg-red-950/30 dark:text-red-300'}`}>{note.text}</p>
    );

    return (
        <Dialog open onOpenChange={o => !o && (drafts.length === 0 || confirm('Close without adding these questions?')) && onClose()}>
            <DialogContent className="max-h-[92dvh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader><DialogTitle className="flex items-center gap-2"><Sparkles className="size-5 text-violet-600" /> Write questions with AI</DialogTitle></DialogHeader>

                {stage === 'ask' ? (
                    <form onSubmit={write} className="space-y-4">
                        <Segmented label="Kind of question" value={ask.type} onChange={v => setAsk({ ...ask, type: v, count: v === 'theory' ? '5' : '10' })}
                            options={[{ value: 'objective', label: 'Objective (options A to D)' }, { value: 'theory', label: 'Theory' }]} />
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="ai-topic">Topic</Label>
                                <Input id="ai-topic" list="ai-topics" value={ask.topic} onChange={e => setAsk({ ...ask, topic: e.target.value })} maxLength={120} placeholder="e.g. Simple interest" autoFocus required />
                                <datalist id="ai-topics">{suggestions.map(t => <option key={t} value={t} />)}</datalist>
                                {info.topics.length > 0 && <p className="text-xs text-slate-500">Start typing to pick a topic from the scheme of work.</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ai-level">Class or level</Label>
                                <Input id="ai-level" value={ask.level} onChange={e => setAsk({ ...ask, level: e.target.value })} maxLength={60} placeholder="e.g. JSS 2" />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ai-count">How many</Label>
                                <Input id="ai-count" type="number" min={1} max={theory ? 10 : 30} value={ask.count} onChange={e => setAsk({ ...ask, count: e.target.value })} />
                                <p className="text-xs text-slate-500">Up to {theory ? 10 : 30} at a time.</p>
                            </div>
                            {(info.styles?.length ?? 0) > 0 && (
                                <div className="space-y-1.5 sm:col-span-2">
                                    <Label>Style of questions</Label>
                                    <Select items={info.styles!.map(x => ({ value: x.key, label: x.label }))} value={ask.style} onValueChange={v => v && setAsk({ ...ask, style: v })}>
                                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                        <SelectContent>{info.styles!.map(x => <SelectItem key={x.key} value={x.key}>{x.label}</SelectItem>)}</SelectContent>
                                    </Select>
                                    <p className="text-xs text-slate-500">{info.styles!.find(x => x.key === ask.style)?.hint}</p>
                                </div>
                            )}
                            <div className="space-y-1.5">
                                <Label>Difficulty</Label>
                                <Select items={LEVELS} value={ask.difficulty} onValueChange={v => v && setAsk({ ...ask, difficulty: v })}>
                                    <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                    <SelectContent>{LEVELS.map(l => <SelectItem key={l.value} value={l.value}>{l.label}</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                            {theory && (
                                <>
                                    <div className="space-y-1.5">
                                        <Label>Parts in each question</Label>
                                        <Select items={PARTS} value={ask.parts} onValueChange={v => v && setAsk({ ...ask, parts: v })}>
                                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                            <SelectContent>{PARTS.map(l => <SelectItem key={l.value} value={l.value}>{l.label}</SelectItem>)}</SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label htmlFor="ai-marks">Marks for each question</Label>
                                        <Input id="ai-marks" type="number" min={1} max={100} value={ask.marks} onChange={e => setAsk({ ...ask, marks: e.target.value })} />
                                    </div>
                                </>
                            )}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ai-notes">Anything else the AI should know (optional)</Label>
                            <Textarea id="ai-notes" rows={2} value={ask.instructions} onChange={e => setAsk({ ...ask, instructions: e.target.value })} maxLength={1500}
                                placeholder="e.g. Use only whole numbers. Include two questions on word problems." />
                        </div>
                        {noteBox}
                        <p className="text-xs text-slate-500">The AI sees only the subject, class, topic and your notes. Nothing is added to the bank until you check the questions and press Add.</p>
                        <DialogFooter>
                            {drafts.length > 0 && <Button type="button" variant="ghost" onClick={() => setStage('review')}>Back to the {drafts.length} written</Button>}
                            <Button type="submit" disabled={busy} className="bg-violet-600 text-white hover:bg-violet-700">
                                {busy ? <Loader2 className="size-4 animate-spin" /> : <Sparkles className="size-4" />} {busy ? 'Writing… this can take a minute' : 'Write questions'}
                            </Button>
                        </DialogFooter>
                    </form>
                ) : (
                    <div className="space-y-4">
                        {noteBox}
                        <ol className="space-y-3">
                            {drafts.map((d, i) => (
                                <li key={i} className={`space-y-2.5 rounded-xl border p-3.5 ${d.keep ? 'border-slate-200 dark:border-white/10' : 'border-dashed border-slate-200 opacity-60 dark:border-white/10'}`}>
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <label className="flex items-center gap-2 text-sm font-medium">
                                            <Checkbox checked={d.keep} onCheckedChange={v => change(i, { keep: !!v })} /> Keep question {i + 1}
                                        </label>
                                        <div className="flex gap-1.5">
                                            {d.type === 'theory' && <Pill tone="info">Theory · {d.marks} marks</Pill>}
                                            <Pill tone={d.difficulty === 'easy' ? 'good' : d.difficulty === 'hard' ? 'bad' : 'info'}>{d.difficulty}</Pill>
                                        </div>
                                    </div>
                                    {d.keep && (
                                        <>
                                            <Textarea aria-label={`Question ${i + 1}`} rows={d.type === 'theory' ? 4 : 2} value={d.text} onChange={e => change(i, { text: e.target.value })} maxLength={5000} />
                                            {d.type === 'theory' ? (
                                                <div className="space-y-1">
                                                    <Label className="text-xs text-slate-500">Marking guide</Label>
                                                    <Textarea aria-label={`Marking guide for question ${i + 1}`} rows={4} value={d.marking_guide ?? ''} onChange={e => change(i, { marking_guide: e.target.value })} maxLength={5000} />
                                                </div>
                                            ) : (
                                                <div className="grid gap-1.5 sm:grid-cols-2">
                                                    {d.options.map((o, j) => (
                                                        <div key={j} className="flex items-center gap-2">
                                                            <input type="radio" name={`right-${i}`} aria-label={`Option ${LETTERS[j]} is right`} checked={d.correct_option === j} onChange={() => change(i, { correct_option: j })} className="size-4 accent-emerald-600" />
                                                            <span className="w-4 text-sm font-medium text-slate-500">{LETTERS[j]}.</span>
                                                            <Input value={o} maxLength={1000} onChange={e => change(i, { options: d.options.map((x, n) => (n === j ? e.target.value : x)) })}
                                                                className={d.correct_option === j ? 'border-emerald-400' : ''} />
                                                        </div>
                                                    ))}
                                                    {d.explanation && <p className="text-xs text-slate-500 sm:col-span-2">Why: {d.explanation}</p>}
                                                </div>
                                            )}
                                        </>
                                    )}
                                </li>
                            ))}
                        </ol>
                        <DialogFooter className="gap-2 sm:justify-between">
                            <Button type="button" variant="outline" onClick={() => { setNote(null); setStage('ask'); }}><Sparkles className="size-4" /> Write more</Button>
                            <Button type="button" disabled={saving || kept.length === 0} onClick={save} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                Add {kept.length} question{kept.length === 1 ? '' : 's'} to the bank
                            </Button>
                        </DialogFooter>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
