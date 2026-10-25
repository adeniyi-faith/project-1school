import { Link, usePage } from '@inertiajs/react';
import { BadgeCheck, CalendarRange, CreditCard, Globe, Palette, Plug, School, ScrollText, SlidersHorizontal, Sparkles, UserCog } from 'lucide-react';
import type { ElementType } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader } from '@/components/app/kit';
import type { PageProps } from '@/Types';

interface Card { title: string; text: string; href: string; icon: ElementType }
interface Section { title: string; cards: Card[] }

const SECTIONS: Section[] = [
    {
        title: 'Your school',
        cards: [
            { title: 'School details', text: 'Name, address, phone, logo and colours. These appear on report cards, receipts and your website.', href: '/school/settings', icon: School },
            { title: 'School type', text: 'Nigerian, British, American or Montessori. Adds the right classes, subjects, terms and grades.', href: '/school/settings/school-type', icon: BadgeCheck },
            { title: 'Terms', text: 'School years and terms, and which term is the current one.', href: '/school/academics/terms', icon: CalendarRange },
            { title: 'Scores and grades', text: 'How a term\'s marks are split (CA1, CA2, Exam) and what grade each score earns.', href: '/school/academics/assessment', icon: SlidersHorizontal },
            { title: 'Report card designs', text: 'The look of report cards: colours, signatures, school stamp and what is shown.', href: '/school/academics/report-card-designs', icon: Palette },
            { title: 'Website', text: 'Your public school website: pages, news, look and contact details.', href: '/school/website', icon: Globe },
        ],
    },
    {
        title: 'People and safety',
        cards: [
            { title: 'Manage users', text: 'Who can sign in, what each person may do, and resetting access.', href: '/school/settings/admins', icon: UserCog },
            { title: 'Activity log', text: 'A record of who changed what, and when. For checking after a mistake.', href: '/school/reports/audit-log', icon: ScrollText },
        ],
    },
    {
        title: 'Connections and tools',
        cards: [
            { title: 'Online payments', text: 'Connect Paystack, Flutterwave or another provider so parents can pay fees online.', href: '/school/fees/online-payments', icon: CreditCard },
            { title: 'Integrations', text: 'Connect outside services such as SMS and email.', href: '/school/settings/integrations', icon: Plug },
            { title: 'AI assistant', text: 'Switch the AI writing helpers on or off, and see how much has been used.', href: '/school/settings/ai', icon: Sparkles },
        ],
    },
];

export default function SettingsHub() {
    const { auth } = usePage<PageProps>().props;
    // Show a card only when the person may open the page it leads to
    const sections = SECTIONS
        .map((s) => ({ ...s, cards: s.cards.filter((c) => !auth.paths || auth.paths.includes(c.href)) }))
        .filter((s) => s.cards.length > 0);

    return (
        <AppLayout title="Settings">
            <div className="space-y-8">
                <PageHeader title="Settings" description="Every setting for your school, in one place. Pick a card to open it." />

                {sections.map((s) => (
                    <section key={s.title} className="space-y-3">
                        <h2 className="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">{s.title}</h2>
                        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {s.cards.map((c) => (
                                <Link key={c.href} href={c.href}
                                    className="flex gap-3 rounded-xl border border-slate-200 bg-white p-4 transition-colors hover:border-indigo-300 hover:bg-indigo-50/40 dark:border-white/10 dark:bg-slate-900 dark:hover:border-indigo-400/40 dark:hover:bg-white/[0.04]">
                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-300"><c.icon className="size-4" /></span>
                                    <span className="min-w-0">
                                        <span className="block text-sm font-semibold text-slate-900 dark:text-white">{c.title}</span>
                                        <span className="mt-0.5 block text-xs leading-relaxed text-slate-500">{c.text}</span>
                                    </span>
                                </Link>
                            ))}
                        </div>
                    </section>
                ))}
            </div>
        </AppLayout>
    );
}
