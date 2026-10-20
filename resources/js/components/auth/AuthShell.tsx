import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';

import AuthLayout from '@/Layouts/AuthLayout';
import { Wordmark } from '@/components/layout/Logo';

/** The Adino wordmark in white, linking home. */
function AdinoLogo() {
    return (
        <a href="/" className="text-white no-underline" aria-label="Adino School Systems home">
            <Wordmark size="lg" />
        </a>
    );
}

interface AuthShellProps {
    title: string;
    topLink: { href: string; label: string };
    heading: string;
    text: string;
    points: string[];
    children: ReactNode;
}

/**
 * Shared frame for the login and register pages: a navy bar on phones,
 * a blue split-screen panel on laptops, and the form on the white side.
 */
export default function AuthShell({ title, topLink, heading, text, points, children }: AuthShellProps) {
    return (
        <AuthLayout>
            <Head title={title}>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&family=Figtree:wght@400;500;600&display=swap" />
            </Head>

            <div className="grid min-h-dvh grid-rows-[auto_1fr] bg-white font-[Figtree,'Segoe_UI',system-ui,sans-serif] text-[#0E1A3A] lg:grid-cols-[minmax(380px,44%)_1fr] lg:grid-rows-1">
                <aside className="relative overflow-hidden bg-[#0C1738] text-white lg:sticky lg:top-0 lg:flex lg:h-dvh lg:flex-col lg:bg-[linear-gradient(180deg,#2A2F9E,#4549D6_60%,#5B63EA)]">
                    <div className="flex h-16 items-center justify-between px-5 lg:h-[84px] lg:px-12">
                        <AdinoLogo />
                        <a href={topLink.href} className="text-sm text-white/80 no-underline hover:text-white lg:hidden">{topLink.label}</a>
                    </div>

                    <div
                        className="pointer-events-none absolute inset-x-[-10%] top-[30%] hidden h-3/5 lg:block"
                        style={{
                            backgroundImage: 'radial-gradient(rgba(214,222,255,.35) 2.4px, transparent 2.8px)',
                            backgroundSize: '22px 22px',
                            WebkitMaskImage: 'radial-gradient(60% 60% at 50% 0%, #000 30%, transparent 75%)',
                            maskImage: 'radial-gradient(60% 60% at 50% 0%, #000 30%, transparent 75%)',
                        }}
                        aria-hidden="true"
                    />

                    <div className="relative z-10 mt-auto hidden px-12 py-10 lg:block">
                        <h2 className="max-w-[14ch] font-[Plus_Jakarta_Sans,Helvetica,Arial,sans-serif] text-4xl font-bold uppercase leading-[1.2]">{heading}</h2>
                        <p className="mt-4 max-w-[36ch] text-[17px] text-white/85">{text}</p>
                        <ul className="mt-7 border-t border-white/20">
                            {points.map((p) => (
                                <li key={p} className="border-b border-white/20 py-3 text-base">{p}</li>
                            ))}
                        </ul>
                    </div>
                </aside>

                <main className="mx-auto w-full max-w-[520px] px-5 pb-12 pt-7 lg:max-w-[560px] lg:self-center lg:px-12 lg:py-16">
                    {children}
                </main>
            </div>
        </AuthLayout>
    );
}

export const headingClass = 'mt-1.5 font-[Plus_Jakarta_Sans,Helvetica,Arial,sans-serif] text-[28px] font-bold leading-tight lg:text-[34px]';
export const inputClass =
    'h-[52px] w-full rounded-xl border-[1.5px] border-[#DCE1EE] bg-white px-3.5 text-base text-[#0E1A3A] outline-none transition focus:border-[#3346E0] focus:ring-[3px] focus:ring-[#3346E0]/15 aria-[invalid=true]:border-[#D0472F]';
export const primaryButtonClass =
    'inline-flex h-[54px] w-full items-center justify-center rounded-full bg-[#0C1738] px-6 font-[Plus_Jakarta_Sans,Helvetica,Arial,sans-serif] text-base font-semibold text-white transition hover:bg-[#18264F] disabled:opacity-60';
export const ghostButtonClass =
    'inline-flex h-[54px] items-center justify-center rounded-full border-[1.5px] border-[#DCE1EE] bg-white px-6 font-[Plus_Jakarta_Sans,Helvetica,Arial,sans-serif] text-base font-semibold text-[#0E1A3A] transition hover:border-[#0E1A3A]';

/** A label, one control and its error message, stacked the way the design shows them. */
export function Field({ label, htmlFor, error, hint, children }: { label: string; htmlFor: string; error?: string; hint?: string; children: ReactNode }) {
    return (
        <div className="grid min-w-0 gap-[7px]">
            <label htmlFor={htmlFor} className="text-[15px] font-semibold">{label}</label>
            {children}
            {hint && !error && <span className="text-[13.5px] text-[#5A6482]">{hint}</span>}
            {error && <span role="alert" className="text-[13.5px] text-[#D0472F]">{error}</span>}
        </div>
    );
}
