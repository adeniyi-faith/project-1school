import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Pencil, Ban, CheckCircle, Globe, Phone, Mail, MapPin, Users, Calendar } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { PageProps, School, AcademicYear } from '@/Types';

interface ShowPageProps extends PageProps {
    school: School & { academic_years: AcademicYear[]; users_count: number };
}

const statusMap = {
    active:    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400',
    inactive:  'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    suspended: 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400',
};

export default function ShowSchool() {
    const { school } = usePage<ShowPageProps>().props;
    const statusClass = statusMap[school.status] ?? statusMap.inactive;

    return (
        <AppLayout breadcrumbs={[
            { label: 'Schools', href: '/super-admin/schools' },
            { label: school.name },
        ]}>
            <Head title={school.name} />

            {/* Header */}
            <div className="flex items-start justify-between mb-6">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/super-admin/schools"><ArrowLeft className="w-4 h-4" /></Link>
                    </Button>
                    <div className="flex items-center gap-3">
                        <div className="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center">
                            {school.logo_url
                                ? <img src={school.logo_url} alt="" className="w-10 h-10 rounded-lg object-cover" />
                                : <span className="text-xl font-bold text-indigo-600">{school.name[0]}</span>
                            }
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-bold text-slate-900 dark:text-white">{school.name}</h1>
                                <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${statusClass}`}>
                                    {school.status}
                                </span>
                            </div>
                            <p className="text-sm text-slate-400 font-mono">{school.slug}</p>
                        </div>
                    </div>
                </div>
                <div className="flex gap-2">
                    {school.status === 'suspended' ? (
                        <Button size="sm" variant="outline" className="text-emerald-600" onClick={() => router.patch(`/super-admin/schools/${school.id}/activate`)}>
                            <CheckCircle className="w-4 h-4 mr-1.5" /> Activate
                        </Button>
                    ) : (
                        <Button size="sm" variant="outline" className="text-amber-600" onClick={() => router.patch(`/super-admin/schools/${school.id}/suspend`)}>
                            <Ban className="w-4 h-4 mr-1.5" /> Suspend
                        </Button>
                    )}
                    <Button size="sm" asChild className="bg-indigo-600 hover:bg-indigo-700 text-white">
                        <Link href={`/super-admin/schools/${school.id}/edit`}>
                            <Pencil className="w-4 h-4 mr-1.5" /> Edit
                        </Link>
                    </Button>
                </div>
            </div>

            <div className="grid grid-cols-3 gap-4">
                {/* Stats */}
                {[
                    { icon: Users, label: 'Total Users', value: school.users_count },
                    { icon: Calendar, label: 'Academic Years', value: school.academic_years?.length ?? 0 },
                    { icon: Globe, label: 'Timezone', value: school.timezone },
                ].map((s) => (
                    <Card key={s.label} className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center">
                                <s.icon className="w-4 h-4 text-indigo-500" />
                            </div>
                            <div>
                                <p className="text-xs text-slate-500">{s.label}</p>
                                <p className="text-sm font-semibold text-slate-900 dark:text-white">{s.value}</p>
                            </div>
                        </CardContent>
                    </Card>
                ))}

                {/* Details */}
                <Card className="col-span-2 dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3"><CardTitle className="text-sm">Contact & Location</CardTitle></CardHeader>
                    <CardContent className="grid grid-cols-2 gap-3 text-sm">
                        {[
                            { icon: Mail, label: 'Email', value: school.email },
                            { icon: Phone, label: 'Phone', value: school.phone },
                            { icon: MapPin, label: 'City', value: school.city },
                            { icon: Globe, label: 'Country', value: school.country },
                        ].map(({ icon: Icon, label, value }) => (
                            <div key={label} className="flex items-start gap-2">
                                <Icon className="w-4 h-4 text-slate-400 mt-0.5 shrink-0" />
                                <div>
                                    <p className="text-xs text-slate-400">{label}</p>
                                    <p className="text-slate-700 dark:text-slate-300">{value ?? '—'}</p>
                                </div>
                            </div>
                        ))}
                        {school.address && (
                            <div className="col-span-2 flex items-start gap-2">
                                <MapPin className="w-4 h-4 text-slate-400 mt-0.5 shrink-0" />
                                <div>
                                    <p className="text-xs text-slate-400">Address</p>
                                    <p className="text-slate-700 dark:text-slate-300">{school.address}</p>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Academic Years */}
                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3"><CardTitle className="text-sm">Academic Years</CardTitle></CardHeader>
                    <CardContent className="space-y-2">
                        {school.academic_years?.length === 0 ? (
                            <p className="text-xs text-slate-400">No academic years yet</p>
                        ) : school.academic_years?.map((y) => (
                            <div key={y.id} className="flex items-center justify-between">
                                <span className="text-sm text-slate-700 dark:text-slate-300">{y.name}</span>
                                {y.is_current && (
                                    <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400 font-medium">
                                        Current
                                    </span>
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
