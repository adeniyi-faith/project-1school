import { Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader, Panel } from '@/components/app/kit';
import { History, Info, Mail, MessageSquare } from 'lucide-react';

interface Props {
    classes: { id: number; name: string }[];
    audiences: { value: string; label: string }[];
    canSms: boolean;
    canEmail: boolean;
    smsReady: boolean;
    subscribers: number | null;
}

export default function Blast({ classes, audiences: baseAudiences, canSms, canEmail, smsReady, subscribers }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        channel: canEmail ? 'email' : 'sms',
        audience: 'all_parents',
        class_id: '',
        subject: '',
        message: '',
    });
    const classItems = classes.map(c => ({ value: String(c.id), label: c.name }));
    const sms = data.channel === 'sms';
    // newsletter subscribers can only be reached by email, and only when the newsletter is set up
    const audiences = !sms && subscribers !== null ? [...baseAudiences, { value: 'subscribers', label: `Newsletter subscribers (${subscribers} confirmed)` }] : baseAudiences;
    if (sms && data.audience === 'subscribers') setData('audience', 'all_parents');
    const texts = Math.max(1, Math.ceil(data.message.length / 160));

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/school/communication/blast');
    }

    const logLink = (
        <Link href="/school/communication/sent" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
            <History className="size-4" /> Sent messages
        </Link>
    );

    return (
        <AppLayout title="SMS / Email Blast">
            <div className="mx-auto max-w-2xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="SMS / Email Blast" description="Send one message to many parents, students or staff. Afterwards you can see who it reached." actions={logLink} />
                <div className="flex flex-wrap gap-2 md:hidden">{logLink}</div>

                <form onSubmit={submit}>
                    <Panel>
                        <div className="space-y-5">
                            <div>
                                <Label>Send by</Label>
                                <div className="mt-2 flex gap-3">
                                    {([['email', 'Email', Mail, canEmail], ['sms', 'SMS', MessageSquare, canSms]] as const).map(([value, label, Icon, allowed]) => allowed && (
                                        <button key={value} type="button" onClick={() => setData('channel', value)}
                                            className={`flex flex-1 items-center gap-2 rounded-lg border p-3 text-sm font-medium transition-colors ${data.channel === value ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'border-slate-200 hover:border-slate-300 dark:border-slate-700'}`}>
                                            <Icon className="size-4" /> {label}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {sms && !smsReady && (
                                <div className="flex gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                                    <Info className="mt-0.5 size-4 shrink-0" />
                                    <span>No SMS provider is connected yet, so texts will not actually go out. They will show as "Failed" in Sent messages, and you can try them again once SMS is set up.</span>
                                </div>
                            )}

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label>Who to send to</Label>
                                    <Select items={audiences} value={data.audience} onValueChange={v => v && setData('audience', v)}>
                                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                        <SelectContent>{audiences.map(a => <SelectItem key={a.value} value={a.value}>{a.label}</SelectItem>)}</SelectContent>
                                    </Select>
                                </div>
                                {data.audience === 'class' && (
                                    <div className="space-y-1.5">
                                        <Label>Class</Label>
                                        <Select items={classItems} value={data.class_id} onValueChange={v => setData('class_id', v ?? '')}>
                                            <SelectTrigger className="w-full"><SelectValue placeholder="Choose a class" /></SelectTrigger>
                                            <SelectContent>{classItems.map(c => <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>)}</SelectContent>
                                        </Select>
                                        {errors.class_id && <p className="text-xs text-red-500">{errors.class_id}</p>}
                                    </div>
                                )}
                            </div>
                            {data.audience === 'subscribers' && <p className="-mt-2 text-xs text-slate-500">This goes out as a newsletter email, with an unsubscribe link, to people who signed up on your website and confirmed. It is not recorded in Sent messages: you will see it under Website &gt; Newsletter.</p>}
                            <p className="-mt-2 text-xs text-slate-500">Parents get one message each, even with several children here. Only active students and current staff are included.</p>

                            {!sms && (
                                <div className="space-y-1.5">
                                    <Label htmlFor="subject">Subject</Label>
                                    <Input id="subject" value={data.subject} onChange={e => setData('subject', e.target.value)} placeholder="e.g. Resumption date for second term" />
                                    {errors.subject && <p className="text-xs text-red-500">{errors.subject}</p>}
                                </div>
                            )}

                            <div className="space-y-1.5">
                                <Label htmlFor="message">Message</Label>
                                <Textarea id="message" value={data.message} onChange={e => setData('message', e.target.value)} rows={6} maxLength={1600}
                                    placeholder={sms ? 'Keep texts short: 160 characters make one SMS.' : 'Write your email here.'} />
                                {sms && <p className="text-xs text-slate-500">{data.message.length} characters, about {texts} SMS per person</p>}
                                {errors.message && <p className="text-xs text-red-500">{errors.message}</p>}
                            </div>

                            <Button type="submit" disabled={processing} className="w-full bg-indigo-600 text-white hover:bg-indigo-700">
                                {processing ? 'Sending…' : `Send ${sms ? 'SMS' : 'email'}`}
                            </Button>
                        </div>
                    </Panel>
                </form>
            </div>
        </AppLayout>
    );
}
