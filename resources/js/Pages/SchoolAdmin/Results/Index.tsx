import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { EmptyState, PageHeader, Panel } from '@/components/app/kit';
import { ResultStatusPill } from '@/components/results/ResultStatus';
import { ChevronRight, ClipboardCheck } from 'lucide-react';
import type { ResultStatus, TermOption } from '@/Types';

interface SheetRow {
    id: number;
    class_name: string;
    status: ResultStatus;
    students: number;
    with_results: number;
    class_average: number | null;
}

interface Props {
    terms: TermOption[];
    termId: number | null;
    sheets: SheetRow[];
}

export default function ResultsIndex({ terms, termId, sheets }: Props) {
    const termItems = terms.map(t => ({ value: String(t.id), label: t.is_current ? `${t.label} (current)` : t.label }));

    return (
        <AppLayout breadcrumbs={[{ label: 'Academic' }, { label: 'Term results' }]}>
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title="Term results"
                    description="Enter CA and exam scores for each class, then submit, approve and publish the results."
                />

                {terms.length === 0 ? (
                    <Panel>
                        <EmptyState icon={ClipboardCheck} title="No terms yet" text="Add a school year on the Terms page first. Its terms are created for you." />
                    </Panel>
                ) : (
                    <>
                        <div className="max-w-xs">
                            <Select
                                items={termItems}
                                value={termId ? String(termId) : ''}
                                onValueChange={v => v && router.get('/school/results', { term_id: v }, { preserveScroll: true })}
                            >
                                <SelectTrigger className="w-full" aria-label="Term"><SelectValue placeholder="Choose a term" /></SelectTrigger>
                                <SelectContent>
                                    {termItems.map(t => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>

                        <Panel flush>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-5">Class</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Students with results</TableHead>
                                        <TableHead className="text-right">Class average</TableHead>
                                        <TableHead className="w-10 pr-5" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {sheets.map(s => (
                                        <TableRow key={s.id} className="cursor-pointer" onClick={() => router.visit(`/school/results/${s.id}`)}>
                                            <TableCell className="pl-5 font-medium text-slate-900 dark:text-white">
                                                <Link href={`/school/results/${s.id}`} onClick={e => e.stopPropagation()}>{s.class_name}</Link>
                                            </TableCell>
                                            <TableCell><ResultStatusPill status={s.status} /></TableCell>
                                            <TableCell className="text-right tabular-nums text-slate-600 dark:text-slate-400">{s.with_results} of {s.students}</TableCell>
                                            <TableCell className="text-right tabular-nums text-slate-600 dark:text-slate-400">{s.class_average !== null ? `${s.class_average}%` : '—'}</TableCell>
                                            <TableCell className="pr-5 text-slate-400"><ChevronRight className="size-4" /></TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </Panel>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
