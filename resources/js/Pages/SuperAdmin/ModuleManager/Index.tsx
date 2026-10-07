import AppLayout from '@/Layouts/AppLayout';
import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Layers, Save, ToggleLeft, ToggleRight } from 'lucide-react';

interface School  { id: number; name: string; status: string; }
interface Module  { slug: string; label: string; is_enabled: boolean; }
interface Props {
    schools: School[];
    selectedSchool: School | null;
    modules: Module[];
    filters: { school_id?: string };
}

export default function ModuleManager({ schools, selectedSchool, modules, filters }: Props) {
    const [schoolId, setSchoolId] = useState(filters.school_id ?? '');
    const [localModules, setLocalModules] = useState<Module[]>(modules);

    const form = useForm<{ school_id: string; modules: Record<string, boolean> }>({
        school_id: filters.school_id ?? '',
        modules: Object.fromEntries(modules.map(m => [m.slug, m.is_enabled])),
    });

    function selectSchool(id: string) {
        setSchoolId(id);
        router.get('/super-admin/module-manager', { school_id: id }, { preserveState: false });
    }

    function toggleModule(slug: string) {
        setLocalModules(prev => prev.map(m => m.slug === slug ? { ...m, is_enabled: !m.is_enabled } : m));
        form.setData('modules', {
            ...form.data.modules,
            [slug]: !form.data.modules[slug],
        });
    }

    function saveAll(e: React.FormEvent) {
        e.preventDefault();
        form.post('/super-admin/module-manager/bulk', {
            data: { school_id: schoolId, modules: form.data.modules },
        });
    }

    return (
        <AppLayout title="Module Manager">
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Module Manager</h1>
                    <p className="text-sm text-slate-500 mt-0.5">Enable or disable individual modules per school</p>
                </div>

                {/* School picker */}
                <Card>
                    <CardContent className="pt-4">
                        <div className="flex items-center gap-3">
                            <div className="w-72">
                                <Select value={schoolId || '_none'} onValueChange={v => selectSchool(v === '_none' ? '' : v)}>
                                    <SelectTrigger><SelectValue placeholder="Select a school..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_none">— Select school —</SelectItem>
                                        {schools.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                            {selectedSchool && (
                                <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${selectedSchool.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'}`}>
                                    {selectedSchool.status}
                                </span>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {!selectedSchool && (
                    <Card>
                        <CardContent className="py-16 text-center text-slate-400">
                            <Layers className="w-12 h-12 mx-auto mb-3 opacity-30" />
                            <p>Select a school to manage its module access</p>
                        </CardContent>
                    </Card>
                )}

                {selectedSchool && (
                    <form onSubmit={saveAll}>
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Layers className="w-4 h-4 text-indigo-500" /> Modules — {selectedSchool.name}
                                </CardTitle>
                                <CardDescription>Toggle to enable or disable each module. Click Save to apply.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    {localModules.map(m => (
                                        <button key={m.slug} type="button" onClick={() => toggleModule(m.slug)}
                                            className={`flex items-center justify-between px-4 py-3 rounded-lg border transition-colors text-sm font-medium ${m.is_enabled ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300' : 'border-slate-200 dark:border-slate-700 text-slate-500'}`}>
                                            <span>{m.label}</span>
                                            {m.is_enabled
                                                ? <ToggleRight className="w-5 h-5 text-indigo-500 shrink-0" />
                                                : <ToggleLeft className="w-5 h-5 text-slate-400 shrink-0" />}
                                        </button>
                                    ))}
                                </div>
                                <div className="mt-5 flex justify-end">
                                    <Button type="submit" disabled={form.processing} className="gap-2">
                                        <Save className="w-4 h-4" /> Save Module Settings
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </form>
                )}
            </div>
        </AppLayout>
    );
}
