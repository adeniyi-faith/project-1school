import AppLayout from '@/Layouts/AppLayout';
import { useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { User, CheckCircle } from 'lucide-react';

interface AuthUser {
    id: number; name: string; email: string; phone: string | null;
    role: string; avatar: string | null; created_at: string;
}
interface Props { user: AuthUser; }

export default function Profile({ user }: Props) {
    const profileForm = useForm({
        name:  user.name,
        email: user.email,
        phone: user.phone ?? '',
    });

    function submitProfile(e: React.FormEvent) {
        e.preventDefault();
        profileForm.put('/profile');
    }

    const initials = user.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
    const roleLabel = (user.role ?? '').replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

    return (
        <AppLayout title="Profile">
            <div className="max-w-2xl space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">My Profile</h1>
                    <p className="text-sm text-slate-500 mt-0.5">Manage your account information</p>
                </div>

                {/* Avatar + role banner */}
                <Card>
                    <CardContent className="pt-5">
                        <div className="flex items-center gap-4">
                            <div className="flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 text-2xl font-bold shrink-0">
                                {initials}
                            </div>
                            <div>
                                <p className="text-lg font-semibold text-slate-900 dark:text-white">{user.name}</p>
                                <p className="text-sm text-slate-500">{user.email}</p>
                                <span className="inline-block mt-1 text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 font-medium">
                                    {roleLabel}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Profile info */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <User className="w-4 h-4 text-indigo-500" /> Personal Information
                        </CardTitle>
                        <CardDescription>Update your name, email, and phone number.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitProfile} className="space-y-4">
                            <div>
                                <Label>Full Name *</Label>
                                <Input
                                    value={profileForm.data.name}
                                    onChange={e => profileForm.setData('name', e.target.value)}
                                    placeholder="Your full name"
                                />
                                {profileForm.errors.name && (
                                    <p className="text-xs text-red-500 mt-1">{profileForm.errors.name}</p>
                                )}
                            </div>
                            <div>
                                <Label>Email Address *</Label>
                                <Input
                                    type="email"
                                    value={profileForm.data.email}
                                    onChange={e => profileForm.setData('email', e.target.value)}
                                    placeholder="your@email.com"
                                />
                                {profileForm.errors.email && (
                                    <p className="text-xs text-red-500 mt-1">{profileForm.errors.email}</p>
                                )}
                            </div>
                            <div>
                                <Label>Phone Number</Label>
                                <Input
                                    value={profileForm.data.phone}
                                    onChange={e => profileForm.setData('phone', e.target.value)}
                                    placeholder="+1234567890"
                                />
                                {profileForm.errors.phone && (
                                    <p className="text-xs text-red-500 mt-1">{profileForm.errors.phone}</p>
                                )}
                            </div>
                            <Button type="submit" disabled={profileForm.processing} className="gap-2">
                                <CheckCircle className="w-4 h-4" /> Save Changes
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
