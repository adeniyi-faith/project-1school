import { useState } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import { ClubDialog, type ClubFields } from '@/components/clubs/ClubForm';
import { Plus, Users } from 'lucide-react';

interface Club { id: number; name: string; description: string | null; meets: string | null; is_active: boolean; patron_id: number | null; patron: string | null; members: number }
interface Props { clubs: Club[]; staff: { id: number; name: string }[]; can: { manage: boolean } }

export default function ClubsIndex({ clubs, staff, can }: Props) {
    const [open, setOpen] = useState(false);
    const [starter, setStarter] = useState<ClubFields | null>(null);
    const start = (name?: string) => { setStarter(name ? { name, description: null, meets: null, patron_id: null, is_active: true } : null); setOpen(true); };
    const add = can.manage ? <Button onClick={() => start()} className="bg-indigo-600 text-white hover:bg-indigo-700"><Plus className="size-4" /> New club</Button> : undefined;

    return (
        <AppLayout title="Clubs">
            <div className="space-y-6">
                <PageHeader title="Clubs and societies" description="Each club's members, their roles (president, secretary ...) and the teacher in charge. Members show on each student's page." actions={add} />
                {add && <div className="flex flex-wrap gap-2 md:hidden">{add}</div>}

                {clubs.length === 0 ? (
                    <Panel>
                        <EmptyState icon={Users} title="No clubs yet" text={can.manage ? 'Start with a common one, or add your own.' : 'The school has not added any clubs yet.'} />
                        {can.manage && (
                            <div className="flex flex-wrap justify-center gap-2 pb-4">
                                {['JETS Club', 'Press Club', 'Debate Society', 'Red Cross', 'Literary and Debating Society', 'Drama Club'].map(n => <Button key={n} variant="outline" onClick={() => start(n)}>{n}</Button>)}
                            </div>
                        )}
                    </Panel>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {clubs.map(c => (
                            <Link key={c.id} href={`/school/clubs/${c.id}`} className="block rounded-xl border border-slate-200 bg-white p-4 transition hover:border-indigo-300 dark:border-white/10 dark:bg-slate-900">
                                <div className="flex items-start justify-between gap-2">
                                    <p className="font-semibold text-slate-900 dark:text-white">{c.name}</p>
                                    {!c.is_active && <Pill>Not running</Pill>}
                                </div>
                                {c.description && <p className="mt-1 line-clamp-2 text-sm text-slate-500">{c.description}</p>}
                                <div className="mt-3 space-y-0.5 text-xs text-slate-500">
                                    <p>{c.members} member{c.members === 1 ? '' : 's'}</p>
                                    <p>Teacher in charge: {c.patron ?? '—'}</p>
                                    {c.meets && <p>Meets: {c.meets}</p>}
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
            <ClubDialog club={starter} open={open} onClose={() => setOpen(false)} staff={staff} />
        </AppLayout>
    );
}
