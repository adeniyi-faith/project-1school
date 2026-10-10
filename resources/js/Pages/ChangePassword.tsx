import AppLayout from '@/Layouts/AppLayout';
import { useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Lock } from 'lucide-react';

export default function ChangePassword() {
    const form = useForm({
        current_password:      '',
        password:              '',
        password_confirmation: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.put('/profile/password', {
            onSuccess: () => form.reset(),
        });
    }

    return (
        <AppLayout title="Change Password">
            <div className="max-w-lg space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Change Password</h1>
                    <p className="text-sm text-slate-500 mt-0.5">Choose a strong password with at least 8 characters</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Lock className="w-4 h-4 text-indigo-500" /> Update Password
                        </CardTitle>
                        <CardDescription>Enter your current password, then set a new one.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <Label>Current Password *</Label>
                                <Input
                                    type="password"
                                    value={form.data.current_password}
                                    onChange={e => form.setData('current_password', e.target.value)}
                                    placeholder="Enter your current password"
                                />
                                {form.errors.current_password && (
                                    <p className="text-xs text-red-500 mt-1">{form.errors.current_password}</p>
                                )}
                            </div>
                            <div>
                                <Label>New Password *</Label>
                                <Input
                                    type="password"
                                    value={form.data.password}
                                    onChange={e => form.setData('password', e.target.value)}
                                    placeholder="Minimum 8 characters"
                                />
                                {form.errors.password && (
                                    <p className="text-xs text-red-500 mt-1">{form.errors.password}</p>
                                )}
                            </div>
                            <div>
                                <Label>Confirm New Password *</Label>
                                <Input
                                    type="password"
                                    value={form.data.password_confirmation}
                                    onChange={e => form.setData('password_confirmation', e.target.value)}
                                    placeholder="Re-enter new password"
                                />
                                {form.errors.password_confirmation && (
                                    <p className="text-xs text-red-500 mt-1">{form.errors.password_confirmation}</p>
                                )}
                            </div>
                            <Button type="submit" disabled={form.processing} className="gap-2">
                                <Lock className="w-4 h-4" /> Update Password
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
