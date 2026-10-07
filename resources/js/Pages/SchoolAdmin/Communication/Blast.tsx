import AppLayout from '@/Layouts/AppLayout';
import { useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { MessageSquare, Mail, Info } from 'lucide-react';

interface SchoolClass { id: number; name: string; }
interface Props { classes: SchoolClass[]; }

export default function Blast({ classes }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        channel:   'email',
        audience:  'all_parents',
        class_id:  '',
        subject:   '',
        message:   '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/school/communication/blast', {
            onSuccess: () => reset(),
        });
    }

    return (
        <AppLayout title="SMS / Email Blast">
            <div className="max-w-2xl space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">SMS / Email Blast</h1>
                    <p className="text-sm text-slate-500 mt-0.5">Send bulk notifications to parents, students, or staff</p>
                </div>

                {/* Info */}
                <div className="flex gap-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 text-sm text-blue-700 dark:text-blue-300">
                    <Info className="w-4 h-4 shrink-0 mt-0.5" />
                    <span>Messages are dispatched via queue. Large blasts may take a few minutes to complete.</span>
                </div>

                <Card>
                    <CardHeader><CardTitle>Compose Blast</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-5">
                            {/* Channel */}
                            <div>
                                <Label>Channel *</Label>
                                <div className="flex gap-3 mt-2">
                                    <button type="button"
                                        onClick={() => setData('channel', 'email')}
                                        className={`flex-1 flex items-center gap-2 p-3 rounded-lg border text-sm font-medium transition-colors ${data.channel === 'email' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'}`}
                                    >
                                        <Mail className="w-4 h-4" /> Email
                                    </button>
                                    <button type="button"
                                        onClick={() => setData('channel', 'sms')}
                                        className={`flex-1 flex items-center gap-2 p-3 rounded-lg border text-sm font-medium transition-colors ${data.channel === 'sms' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'}`}
                                    >
                                        <MessageSquare className="w-4 h-4" /> SMS
                                    </button>
                                </div>
                            </div>

                            {/* Audience */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>Audience *</Label>
                                    <Select value={data.audience} onValueChange={v => setData('audience', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all_parents">All Parents</SelectItem>
                                            <SelectItem value="all_students">All Students</SelectItem>
                                            <SelectItem value="all_staff">All Staff</SelectItem>
                                            <SelectItem value="class">Specific Class</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                {data.audience === 'class' && (
                                    <div>
                                        <Label>Class</Label>
                                        <Select value={data.class_id} onValueChange={v => setData('class_id', v)}>
                                            <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
                                            <SelectContent>
                                                {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                            </SelectContent>
                                        </Select>
                                        {errors.class_id && <p className="text-xs text-red-500 mt-1">{errors.class_id}</p>}
                                    </div>
                                )}
                            </div>

                            {/* Subject (email only) */}
                            {data.channel === 'email' && (
                                <div>
                                    <Label>Subject *</Label>
                                    <Input value={data.subject} onChange={e => setData('subject', e.target.value)} placeholder="Email subject line" />
                                    {errors.subject && <p className="text-xs text-red-500 mt-1">{errors.subject}</p>}
                                </div>
                            )}

                            {/* Message */}
                            <div>
                                <Label>Message *</Label>
                                <Textarea
                                    value={data.message}
                                    onChange={e => setData('message', e.target.value)}
                                    rows={6}
                                    placeholder={data.channel === 'sms' ? 'SMS text (max 160 chars per message)...' : 'Email body...'}
                                />
                                {data.channel === 'sms' && (
                                    <p className="text-xs text-slate-400 mt-1">{data.message.length} characters</p>
                                )}
                                {errors.message && <p className="text-xs text-red-500 mt-1">{errors.message}</p>}
                            </div>

                            <Button type="submit" disabled={processing} className="w-full">
                                {processing ? 'Queuing...' : `Send ${data.channel === 'sms' ? 'SMS' : 'Email'} Blast`}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
