import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EmptyState, PageHeader, Panel } from '@/components/app/kit';
import { CertificateList } from '@/components/certificates/CertificateList';
import { Award, Palette, Search } from 'lucide-react';
import type { CertificateRow } from '@/Types';

interface Props {
    certificates: {
        data: CertificateRow[];
        total: number;
        from: number | null;
        to: number | null;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: { type: string | null; search: string };
    types: { value: string; label: string }[];
    canIssue: boolean;
}

export default function CertificatesIndex({ certificates, filters, types, canIssue }: Props) {
    const [search, setSearch] = useState(filters.search);
    const typeItems = [{ value: 'all', label: 'All certificates' }, ...types];
    const designLink = (
        <Link href="/school/certificates/designs" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
            <Palette className="size-4" /> Certificate designs
        </Link>
    );

    function apply(next: { type?: string | null; search?: string }) {
        const params = { type: filters.type ?? '', search, ...next };
        router.get('/school/certificates', {
            ...(params.type && params.type !== 'all' ? { type: params.type } : {}),
            ...(params.search ? { search: params.search } : {}),
        }, { preserveState: true, preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={[{ label: 'Students', href: '/school/students' }, { label: 'Certificates' }]}>
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title="Certificates"
                    description="Every testimonial and transfer certificate the school has issued. To issue one, open the student's page and choose the Certificates tab."
                    actions={designLink}
                />
                <div className="md:hidden">{designLink}</div>

                <div className="flex flex-wrap gap-2">
                    <form onSubmit={e => { e.preventDefault(); apply({ search }); }} className="relative min-w-60 flex-1">
                        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <Input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search by name, admission no. or certificate no." className="pl-9" />
                    </form>
                    <div className="w-56">
                        <Select items={typeItems} value={filters.type ?? 'all'} onValueChange={v => apply({ type: v })}>
                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>{typeItems.map(t => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}</SelectContent>
                        </Select>
                    </div>
                </div>

                <Panel>
                    {certificates.data.length === 0 ? (
                        <EmptyState icon={Award} title="No certificates yet" text={filters.search || filters.type ? 'Nothing matches your search.' : 'Certificates you issue from a student\'s page appear here.'} />
                    ) : (
                        <>
                            <CertificateList items={certificates.data} canRevoke={canIssue} showStudent />
                            {(certificates.prev_page_url || certificates.next_page_url) && (
                                <div className="mt-4 flex items-center justify-between text-sm text-slate-500">
                                    <span>{certificates.from}–{certificates.to} of {certificates.total}</span>
                                    <div className="flex gap-2">
                                        {certificates.prev_page_url && <Button variant="outline" size="sm" onClick={() => router.get(certificates.prev_page_url!)}>Previous</Button>}
                                        {certificates.next_page_url && <Button variant="outline" size="sm" onClick={() => router.get(certificates.next_page_url!)}>Next</Button>}
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </Panel>
            </div>
        </AppLayout>
    );
}
