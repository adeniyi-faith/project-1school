import { useState } from 'react';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { naira } from '@/lib/format';
import { Loader2, Sparkles } from 'lucide-react';
import type { AiWriterInfo } from '@/components/cbt/AiQuestionWriter';

/** Turn the picture the AI made into a small JPEG file, so it is attached like any picture the teacher uploads */
function toFile(dataUrl: string): Promise<File> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => {
            const scale = Math.min(1, 1024 / Math.max(img.width, img.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(img.width * scale);
            canvas.height = Math.round(img.height * scale);
            const ctx = canvas.getContext('2d');
            if (!ctx) return reject(new Error('no canvas'));
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(blob => (blob ? resolve(new File([blob], 'picture.jpg', { type: 'image/jpeg' })) : reject(new Error('no blob'))), 'image/jpeg', 0.9);
        };
        img.onerror = () => reject(new Error('bad picture'));
        img.src = dataUrl;
    });
}

/** Describe a picture, choose a style, look at what the AI made, and attach it to the question only if it is right */
export default function PictureMaker({ url, info, onUse }: { url: string; info: NonNullable<AiWriterInfo['images']>; onUse: (file: File) => void }) {
    const [open, setOpen] = useState(false);
    const [description, setDescription] = useState('');
    const [style, setStyle] = useState(info.default_style);
    const [busy, setBusy] = useState(false);
    const [picture, setPicture] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const items = info.styles.map(s => ({ value: s.key, label: s.label }));

    async function make() {
        setBusy(true);
        setError(null);
        try {
            const { data } = await axios.post<{ image: string }>(`${url}/ai-image`, { description, style });
            setPicture(data.image);
        } catch (e) {
            const errs = axios.isAxiosError(e) ? e.response?.data?.errors : null;
            setError(errs ? ((Object.values(errs)[0] as string[] | undefined)?.[0] ?? null) : null);
            if (!errs) setError('The picture could not be made just now. Try again.');
        } finally {
            setBusy(false);
        }
    }

    async function use() {
        if (!picture) return;
        try {
            onUse(await toFile(picture));
            setOpen(false);
            setPicture(null);
        } catch {
            setError('This picture could not be attached. Make another one.');
        }
    }

    if (!open) {
        return <Button type="button" variant="outline" size="sm" onClick={() => setOpen(true)} className="border-violet-200 text-violet-700 hover:bg-violet-50 dark:border-violet-500/30 dark:text-violet-300"><Sparkles className="size-4" /> Make a picture with AI</Button>;
    }

    return (
        <div className="space-y-3 rounded-lg border border-violet-200 bg-violet-50/40 p-3 dark:border-violet-500/30 dark:bg-violet-500/5">
            <div className="space-y-1.5">
                <Label htmlFor="pic-desc">What should the picture show?</Label>
                <Textarea id="pic-desc" rows={2} value={description} onChange={e => setDescription(e.target.value)} maxLength={600} placeholder="e.g. A farmer harvesting yams on a farm in the morning" />
            </div>
            <div className="space-y-1.5">
                <Label htmlFor="pic-style">Style of picture</Label>
                <Select items={items} value={style} onValueChange={v => v && setStyle(v)}>
                    <SelectTrigger id="pic-style" className="w-full"><SelectValue /></SelectTrigger>
                    <SelectContent>{items.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                </Select>
                <p className="text-xs text-slate-500">{info.styles.find(s => s.key === style)?.hint}</p>
            </div>
            {error && <p className="text-sm text-red-600" role="status">{error}</p>}
            {picture && (
                <div className="space-y-2">
                    <img src={picture} alt="The picture the AI made" className="max-h-64 rounded border border-slate-200 bg-white dark:border-white/10" />
                    <p className="text-xs text-amber-700 dark:text-amber-300">Check this picture is correct before you use it. The AI can make mistakes, especially in science and maths.</p>
                </div>
            )}
            <div className="flex flex-wrap items-center justify-end gap-2">
                <span className="mr-auto text-xs text-slate-500">Each picture uses about {naira(info.cost)} of your school's AI allowance.</span>
                <Button type="button" variant="outline" size="sm" onClick={() => { setOpen(false); setPicture(null); setError(null); }}>Cancel</Button>
                <Button type="button" size="sm" onClick={make} disabled={busy || description.trim().length < 5} className="bg-violet-600 text-white hover:bg-violet-700">
                    {busy ? <Loader2 className="size-4 animate-spin" /> : <Sparkles className="size-4" />} {busy ? 'Making…' : picture ? 'Make another' : 'Make the picture'}
                </Button>
                {picture && <Button type="button" size="sm" onClick={use}>Use this picture</Button>}
            </div>
        </div>
    );
}
