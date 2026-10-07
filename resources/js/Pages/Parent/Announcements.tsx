import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/components/ui/card';
import { Megaphone } from 'lucide-react';

interface Ann { id: number; title: string; body: string; pinned: boolean; date: string | null; }
interface Props { linked: boolean; guardian: { name: string } | null; announcements: Ann[]; }

export default function ParentAnnouncements({ linked, announcements }: Props) {
    if (!linked) {
        return (
            <AppLayout title="Announcements">
                <div className="flex flex-col items-center justify-center py-24 text-center">
                    <Megaphone className="w-14 h-14 text-slate-300 mb-4" />
                    <h2 className="text-lg font-semibold text-slate-600 dark:text-slate-400">Account not linked</h2>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="Announcements">
            <div className="space-y-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">School Announcements</h1>
                    <p className="text-sm text-slate-500">{announcements.length} announcement{announcements.length !== 1 ? 's' : ''}</p>
                </div>

                {announcements.length === 0 ? (
                    <Card><CardContent className="py-16 text-center text-slate-400">No announcements yet.</CardContent></Card>
                ) : (
                    <div className="space-y-3">
                        {announcements.map(a => (
                            <Card key={a.id} className={a.pinned ? 'border-amber-300 dark:border-amber-700' : ''}>
                                <CardContent className="p-5">
                                    <div className="flex items-start gap-3">
                                        <div className="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center shrink-0">
                                            <Megaphone className="w-4 h-4 text-violet-600 dark:text-violet-400" />
                                        </div>
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2 mb-1">
                                                {a.pinned && <span className="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">Pinned</span>}
                                                <h3 className="font-semibold text-slate-800 dark:text-slate-200 text-sm">{a.title}</h3>
                                            </div>
                                            <p className="text-sm text-slate-600 dark:text-slate-400">{a.body}</p>
                                            {a.date && <p className="text-xs text-slate-400 mt-2">{a.date}</p>}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
