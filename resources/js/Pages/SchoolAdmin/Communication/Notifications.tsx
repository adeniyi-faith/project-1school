import AppLayout from '@/Layouts/AppLayout';
import { router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Bell, CheckCheck } from 'lucide-react';

interface Notification {
    id: number;
    type: string;
    title: string;
    body: string;
    channel: string;
    read_at?: string;
    created_at: string;
}
interface PaginatedNotifications {
    data: Notification[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}
interface Props {
    notifications: PaginatedNotifications;
    unread_count:  number;
}

export default function Notifications({ notifications, unread_count }: Props) {
    function markRead(id: number) {
        router.put(`/school/communication/notifications/${id}/read`, {}, { preserveScroll: true });
    }
    function markAllRead() {
        router.put('/school/communication/notifications/read-all', {}, { preserveScroll: true });
    }

    return (
        <AppLayout title="Notifications">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Notifications</h1>
                        <p className="text-sm text-slate-500 mt-0.5">
                            {unread_count > 0 ? `${unread_count} unread notification${unread_count > 1 ? 's' : ''}` : 'All caught up'}
                        </p>
                    </div>
                    {unread_count > 0 && (
                        <Button variant="outline" onClick={markAllRead} className="gap-2">
                            <CheckCheck className="w-4 h-4" /> Mark all as read
                        </Button>
                    )}
                </div>

                <Card>
                    <CardHeader><CardTitle>All Notifications ({notifications.total})</CardTitle></CardHeader>
                    <CardContent className="divide-y divide-slate-100 dark:divide-slate-800 p-0">
                        {notifications.data.length === 0 && (
                            <div className="py-12 text-center text-slate-400">
                                <Bell className="w-8 h-8 mx-auto mb-2 opacity-30" />
                                <p>No notifications yet</p>
                            </div>
                        )}
                        {notifications.data.map(n => (
                            <div
                                key={n.id}
                                className={`flex items-start gap-3 px-4 py-3 ${!n.read_at ? 'bg-indigo-50/50 dark:bg-indigo-950/20' : ''}`}
                            >
                                <div className={`mt-1 w-2 h-2 rounded-full shrink-0 ${!n.read_at ? 'bg-indigo-500' : 'bg-transparent'}`} />
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <p className={`text-sm ${!n.read_at ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300'}`}>
                                            {n.title}
                                        </p>
                                        <Badge variant="secondary" className="text-xs">{n.channel}</Badge>
                                        <Badge variant="outline" className="text-xs">{n.type}</Badge>
                                    </div>
                                    <p className="text-xs text-slate-500 mt-0.5">{n.body}</p>
                                    <p className="text-xs text-slate-400 mt-1">{new Date(n.created_at).toLocaleString()}</p>
                                </div>
                                {!n.read_at && (
                                    <Button size="sm" variant="ghost" onClick={() => markRead(n.id)} className="shrink-0 text-xs">
                                        Mark read
                                    </Button>
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>

                {notifications.last_page > 1 && (
                    <div className="flex justify-center gap-1">
                        {notifications.links.map((link, i) => (
                            <Button key={i} size="sm" variant={link.active ? 'default' : 'outline'} disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
