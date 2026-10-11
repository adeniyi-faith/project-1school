import { router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { useEffect, useState } from 'react';
import { BedDouble, BookOpen, Bus, Calculator, ConciergeBell, GraduationCap, Landmark, Package, School, UserRound, Users, type LucideIcon } from 'lucide-react';

import AuthShell, { headingClass } from '@/components/auth/AuthShell';
import type { PageProps } from '@/Types';

interface DemoAccount {
    key: string;
    label: string;
    text: string;
    available: boolean;
}

interface DemoPickProps extends PageProps {
    accounts: DemoAccount[];
    current: string | null;
}

const icons: Record<string, LucideIcon> = {
    admin: School,
    principal: Landmark,
    teacher: GraduationCap,
    accountant: Calculator,
    parent: Users,
    student: UserRound,
    librarian: BookOpen,
    receptionist: ConciergeBell,
    driver: Bus,
    warden: BedDouble,
    'store-manager': Package,
};

// The first six are shown as big cards; the rest sit under "Other staff"
const MAIN = ['admin', 'principal', 'teacher', 'accountant', 'parent', 'student'];

/** The demo's first page: choose who to look around as. */
export default function DemoPick() {
    const { accounts, current, flash } = usePage<DemoPickProps>().props;
    const [going, setGoing] = useState<string | null>(null);

    useEffect(() => {
        if (flash?.error) toast.error(flash.error);
    }, [flash]);

    function enter(key: string) {
        setGoing(key);
        router.post(`/demo/${key}`, {}, { onFinish: () => setGoing(null) });
    }

    const main = accounts.filter(a => MAIN.includes(a.key));
    const others = accounts.filter(a => !MAIN.includes(a.key));

    function card(a: DemoAccount, big: boolean) {
        const Icon = icons[a.key] ?? UserRound;
        const isCurrent = current === a.key;
        return (
            <button
                key={a.key}
                type="button"
                onClick={() => enter(a.key)}
                disabled={!a.available || going !== null}
                className={`group flex w-full items-start gap-3 rounded-2xl border-[1.5px] bg-white text-left transition hover:border-[#3346E0] hover:shadow-[0_6px_18px_-8px_rgba(51,70,224,.35)] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#3346E0]/25 disabled:cursor-not-allowed disabled:opacity-55 disabled:hover:border-[#DCE1EE] disabled:hover:shadow-none ${isCurrent ? 'border-[#3346E0]' : 'border-[#DCE1EE]'} ${big ? 'p-4' : 'px-3.5 py-3'}`}
            >
                <span className={`grid shrink-0 place-items-center rounded-xl bg-[#EEF0FF] text-[#3346E0] ${big ? 'size-10' : 'size-8'}`}>
                    <Icon className={big ? 'size-5' : 'size-4'} aria-hidden="true" />
                </span>
                <span className="min-w-0">
                    <span className="flex flex-wrap items-center gap-x-2 font-[Plus_Jakarta_Sans,Helvetica,Arial,sans-serif] text-[15.5px] font-semibold text-[#0E1A3A]">
                        {a.label}
                        {isCurrent && <span className="rounded-full bg-[#EEF0FF] px-2 py-0.5 text-[11.5px] font-medium text-[#3346E0]">You are here</span>}
                        {going === a.key && <span className="text-[12.5px] font-normal text-[#5A6482]">Opening…</span>}
                    </span>
                    {big && <span className="mt-0.5 block text-[13.5px] leading-snug text-[#5A6482]">{a.available ? a.text : 'Needs the sample pupils, which are not loaded yet.'}</span>}
                </span>
            </button>
        );
    }

    return (
        <AuthShell
            title="Try the demo"
            topLink={{ href: '/login', label: 'Log in' }}
            heading="See it as they do"
            text="Every person in a school sees SchoolRuns differently. Pick one and look around a sample Nigerian school."
            points={['No sign-up and no password', 'Real-looking sample data', 'Nothing you click changes anything']}
        >
            <p className="text-[15px] font-medium text-[#3346E0]">Demo school</p>
            <h1 className={headingClass}>Who do you want to be?</h1>
            <p className="mt-2 text-[15.5px] text-[#5A6482]">
                Choose an account type. You can come back and switch at any time. The demo is read-only, so you can click around freely.
            </p>

            <div className="mt-6 grid gap-2.5 sm:grid-cols-2">{main.map(a => card(a, true))}</div>

            {others.length > 0 && (
                <>
                    <h2 className="mt-7 text-[13px] font-semibold uppercase tracking-wide text-[#5A6482]">Other staff</h2>
                    <div className="mt-2.5 grid grid-cols-2 gap-2.5 sm:grid-cols-3">{others.map(a => card(a, false))}</div>
                </>
            )}

            <div className="mt-8 grid gap-2 text-center text-[15px] text-[#5A6482]">
                <p>
                    Ready for your own school?{' '}
                    <a href="/register" className="font-medium text-[#3346E0] hover:underline">Register your school</a>
                </p>
                {!current && (
                    <p>
                        Already have an account?{' '}
                        <a href="/login" className="font-medium text-[#3346E0] hover:underline">Log in</a>
                    </p>
                )}
            </div>
        </AuthShell>
    );
}
