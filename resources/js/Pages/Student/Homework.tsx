import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { NotebookPen, Clock } from 'lucide-react';

interface HWItem { id: number; title: string; description: string | null; subject: string | null; due: string; due_label: string; overdue: boolean; }
interface Props {
    linked: boolean;
    student: { full_name: string; class: string | null } | null;
    homework: HWItem[];
}

export default function Homework({ linked, student, homework }: Props) {
    if (!linked) {
        return (
            <AppLayout title="My Homework">
                <div className="flex flex-col items-center justify-center py-24 text-center">
                    <NotebookPen className="w-14 h-14 text-slate-300 mb-4" />
                    <h2 className="text-lg font-semibold text-slate-600 dark:text-slate-400">Account not linked</h2>
                </div>
            </AppLayout>
        );
    }

    const pending  = homework.filter(h => !h.overdue);
    const overdue  = homework.filter(h => h.overdue);

    return (
        <AppLayout title="My Homework">
            <div className="space-y-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">My Homework</h1>
                    <p className="text-sm text-slate-500">{student?.class}</p>
                </div>

                {overdue.length > 0 && (
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wider text-red-500 mb-2">Overdue ({overdue.length})</p>
                        <div className="space-y-2">
                            {overdue.map(h => <HWCard key={h.id} item={h} />)}
                        </div>
                    </div>
                )}

                <div>
                    <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">
                        {pending.length > 0 ? `Upcoming (${pending.length})` : 'No pending homework'}
                    </p>
                    <div className="space-y-2">
                        {pending.map(h => <HWCard key={h.id} item={h} />)}
                    </div>
                </div>

                {homework.length === 0 && (
                    <Card><CardContent className="py-16 text-center text-slate-400">No homework assigned yet.</CardContent></Card>
                )}
            </div>
        </AppLayout>
    );
}

function HWCard({ item }: { item: HWItem }) {
    return (
        <Card className={cn(item.overdue && 'border-red-200 dark:border-red-900/50')}>
            <CardContent className="p-4 flex items-start justify-between gap-3">
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1">
                        {item.subject && <Badge variant="secondary" className="text-[10px] h-4">{item.subject}</Badge>}
                        {item.overdue && <Badge className="text-[10px] h-4 bg-red-500">Overdue</Badge>}
                    </div>
                    <p className="font-medium text-slate-800 dark:text-slate-200 text-sm">{item.title}</p>
                    {item.description && <p className="text-xs text-slate-500 mt-0.5 line-clamp-2">{item.description}</p>}
                </div>
                <div className={cn('flex items-center gap-1 text-xs shrink-0', item.overdue ? 'text-red-500' : 'text-slate-400')}>
                    <Clock className="w-3 h-3" />
                    <span>{item.due_label}</span>
                </div>
            </CardContent>
        </Card>
    );
}
