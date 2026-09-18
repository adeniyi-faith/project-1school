import AppLayout from '@/Layouts/AppLayout';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Plus, Pencil } from 'lucide-react';

interface EmailTemplate {
    id: number;
    name: string;
    slug: string;
    subject: string;
    body: string;
    variables?: string[];
    is_active: boolean;
}
interface Props { templates: EmailTemplate[]; }

const emptyForm = { name: '', slug: '', subject: '', body: '', variables_raw: '' };

export default function EmailTemplates({ templates }: Props) {
    const [open, setOpen]       = useState(false);
    const [editing, setEditing] = useState<EmailTemplate | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({ ...emptyForm });

    function openCreate() { reset(); setEditing(null); setOpen(true); }
    function openEdit(t: EmailTemplate) {
        setEditing(t);
        setData({
            name: t.name, slug: t.slug, subject: t.subject, body: t.body,
            variables_raw: (t.variables ?? []).join(', '),
        });
        setOpen(true);
    }
    function submit(e: React.FormEvent) {
        e.preventDefault();
        const payload = {
            ...data,
            variables: data.variables_raw ? data.variables_raw.split(',').map(v => v.trim()).filter(Boolean) : [],
        };
        if (editing) {
            put(`/school/communication/email-templates/${editing.id}`, { data: payload, onSuccess: () => { reset(); setOpen(false); } } as Parameters<typeof put>[1]);
        } else {
            post('/school/communication/email-templates', { data: payload, onSuccess: () => { reset(); setOpen(false); } } as Parameters<typeof post>[1]);
        }
    }

    return (
        <AppLayout title="Email Templates">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Email Templates</h1>
                        <p className="text-sm text-slate-500 mt-0.5">Manage reusable email templates with variable substitution</p>
                    </div>
                    <Button onClick={openCreate} className="gap-2"><Plus className="w-4 h-4" /> New Template</Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {templates.length === 0 && (
                        <Card className="col-span-3"><CardContent className="py-12 text-center text-slate-400">No templates yet</CardContent></Card>
                    )}
                    {templates.map(t => (
                        <Card key={t.id} className={!t.is_active ? 'opacity-60' : ''}>
                            <CardHeader className="pb-2">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-base">{t.name}</CardTitle>
                                    <Badge variant={t.is_active ? 'default' : 'secondary'}>{t.is_active ? 'Active' : 'Inactive'}</Badge>
                                </div>
                                <p className="text-xs text-slate-400 font-mono">{t.slug}</p>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">{t.subject}</p>
                                <p className="text-xs text-slate-400 line-clamp-2">{t.body}</p>
                                {t.variables && t.variables.length > 0 && (
                                    <div className="flex flex-wrap gap-1 mt-2">
                                        {t.variables.map(v => (
                                            <span key={v} className="text-xs px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-slate-500">{`{{${v}}}`}</span>
                                        ))}
                                    </div>
                                )}
                                <Button size="sm" variant="outline" className="mt-3 w-full gap-1" onClick={() => openEdit(t)}>
                                    <Pencil className="w-3 h-3" /> Edit
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>

            {/* Dialog */}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
                    <DialogHeader><DialogTitle>{editing ? 'Edit Template' : 'New Template'}</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Name *</Label>
                                <Input value={data.name} onChange={e => setData('name', e.target.value)} placeholder="Fee Reminder" />
                                {errors.name && <p className="text-xs text-red-500 mt-1">{errors.name}</p>}
                            </div>
                            <div>
                                <Label>Slug *</Label>
                                <Input value={data.slug} onChange={e => setData('slug', e.target.value.toLowerCase().replace(/\s+/g, '-'))} placeholder="fee-reminder" className="font-mono" />
                                {errors.slug && <p className="text-xs text-red-500 mt-1">{errors.slug}</p>}
                            </div>
                        </div>
                        <div>
                            <Label>Subject *</Label>
                            <Input value={data.subject} onChange={e => setData('subject', e.target.value)} placeholder="e.g. Fee Reminder for {{student_name}}" />
                            {errors.subject && <p className="text-xs text-red-500 mt-1">{errors.subject}</p>}
                        </div>
                        <div>
                            <Label>Body *</Label>
                            <Textarea value={data.body} onChange={e => setData('body', e.target.value)} rows={6} placeholder="Dear {{parent_name}}, ..." />
                            {errors.body && <p className="text-xs text-red-500 mt-1">{errors.body}</p>}
                        </div>
                        <div>
                            <Label>Variables (comma separated)</Label>
                            <Input value={data.variables_raw} onChange={e => setData('variables_raw', e.target.value)} placeholder="student_name, parent_name, amount" className="font-mono" />
                            <p className="text-xs text-slate-400 mt-1">Use {`{{variable}}`} syntax in subject/body</p>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing}>{editing ? 'Update' : 'Create'}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
