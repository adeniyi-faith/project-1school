import AppLayout from '@/Layouts/AppLayout';
import { useForm, usePage } from '@inertiajs/react';
import { useState, useRef } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import {
    Globe, CreditCard, Mail, MapPin, Wrench, HardDrive,
    Bell, ShieldCheck, Save, Upload, CheckCircle, Eye, EyeOff, AlertTriangle,
} from 'lucide-react';

interface Props {
    settings: Record<string, string>;
    logoUrl: string | null;
    faviconUrl: string | null;
}

const TABS = [
    { id: 'general',      label: 'Platform',           icon: Globe },
    { id: 'payment',      label: 'Payment Gateway',     icon: CreditCard },
    { id: 'smtp',         label: 'Platform SMTP',       icon: Mail },
    { id: 'localization', label: 'Localization',         icon: MapPin },
    { id: 'storage',      label: 'Storage & Uploads',   icon: HardDrive },
    { id: 'templates',    label: 'Notification Templates', icon: Bell },
    { id: 'audit',        label: 'Audit Log',           icon: ShieldCheck },
    { id: 'maintenance',  label: 'Maintenance',          icon: Wrench },
] as const;

type TabId = typeof TABS[number]['id'];

const TIMEZONES = [
    'UTC', 'Asia/Dhaka', 'Asia/Kolkata', 'Asia/Karachi', 'Asia/Dubai',
    'America/New_York', 'America/Chicago', 'America/Los_Angeles',
    'Europe/London', 'Europe/Berlin', 'Africa/Cairo', 'Australia/Sydney',
];
const CURRENCIES   = ['USD','EUR','GBP','BDT','INR','PKR','AED','SAR','EGP','AUD','CAD'];
const DATE_FORMATS = ['Y-m-d','d/m/Y','m/d/Y','d-m-Y','F j, Y'];
const LANGUAGES    = [{ value:'en', label:'English' },{ value:'bn', label:'Bengali' },{ value:'ar', label:'Arabic' }];

function SuccessBanner({ message }: { message: string }) {
    return (
        <div className="flex items-center gap-2 text-sm text-green-700 bg-green-50 dark:bg-green-950/30 dark:text-green-400 border border-green-200 dark:border-green-800 rounded-lg px-4 py-2">
            <CheckCircle className="w-4 h-4 shrink-0" /> {message}
        </div>
    );
}

/* ── Platform General ── */
function GeneralTab({ settings, logoUrl, faviconUrl }: Pick<Props, 'settings' | 'logoUrl' | 'faviconUrl'>) {
    const form = useForm<{
        platform_name: string; support_email: string; support_phone: string;
        footer_copyright: string; logo: File | null; favicon: File | null;
    }>({
        platform_name:    settings.platform_name    ?? 'Genius SMS',
        support_email:    settings.support_email    ?? '',
        support_phone:    settings.support_phone    ?? '',
        footer_copyright: settings.footer_copyright ?? '',
        logo:    null,
        favicon: null,
    });
    const [logoPreview,    setLogoPreview]    = useState<string | null>(logoUrl);
    const [faviconPreview, setFaviconPreview] = useState<string | null>(faviconUrl);
    const logoRef    = useRef<HTMLInputElement>(null);
    const faviconRef = useRef<HTMLInputElement>(null);
    const { flash }  = usePage<any>().props;

    function handleFile(field: 'logo' | 'favicon', file: File | null) {
        if (!file) return;
        form.setData(field, file);
        const url = URL.createObjectURL(file);
        if (field === 'logo')    setLogoPreview(url);
        if (field === 'favicon') setFaviconPreview(url);
    }

    return (
        <form onSubmit={e => { e.preventDefault(); form.post('/super-admin/settings/general', { forceFormData: true }); }}>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Platform Identity</CardTitle>
                    <CardDescription>Configure the platform name, logo and contact info shown to schools.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    {flash?.success && <SuccessBanner message={flash.success} />}

                    {/* Logo & Favicon */}
                    <div className="flex flex-wrap gap-8">
                        <div>
                            <Label className="block mb-3">Platform Logo</Label>
                            <div className="flex items-center gap-4">
                                <div className="w-24 h-24 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden bg-slate-50 dark:bg-slate-800">
                                    {logoPreview
                                        ? <img src={logoPreview} alt="Logo" className="w-full h-full object-contain p-1" />
                                        : <span className="text-xs text-slate-400">No logo</span>}
                                </div>
                                <div>
                                    <input ref={logoRef} type="file" accept="image/*" className="hidden"
                                        onChange={e => handleFile('logo', e.target.files?.[0] ?? null)} />
                                    <Button type="button" variant="outline" size="sm" className="gap-2"
                                        onClick={() => logoRef.current?.click()}>
                                        <Upload className="w-3.5 h-3.5" /> Upload
                                    </Button>
                                    <p className="text-xs text-slate-400 mt-1">PNG/SVG, max 2MB</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <Label className="block mb-3">Favicon</Label>
                            <div className="flex items-center gap-4">
                                <div className="w-12 h-12 rounded-lg border-2 border-dashed border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden bg-slate-50 dark:bg-slate-800">
                                    {faviconPreview
                                        ? <img src={faviconPreview} alt="Favicon" className="w-full h-full object-contain" />
                                        : <span className="text-[10px] text-slate-400">ico</span>}
                                </div>
                                <div>
                                    <input ref={faviconRef} type="file" accept="image/*" className="hidden"
                                        onChange={e => handleFile('favicon', e.target.files?.[0] ?? null)} />
                                    <Button type="button" variant="outline" size="sm" className="gap-2"
                                        onClick={() => faviconRef.current?.click()}>
                                        <Upload className="w-3.5 h-3.5" /> Upload
                                    </Button>
                                    <p className="text-xs text-slate-400 mt-1">Max 512KB</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-slate-100 dark:border-slate-800 pt-4">
                        <div>
                            <Label>Platform Name *</Label>
                            <Input value={form.data.platform_name} onChange={e => form.setData('platform_name', e.target.value)} />
                            {form.errors.platform_name && <p className="text-xs text-red-500 mt-1">{form.errors.platform_name}</p>}
                        </div>
                        <div>
                            <Label>Support Email</Label>
                            <Input type="email" value={form.data.support_email} onChange={e => form.setData('support_email', e.target.value)} />
                        </div>
                        <div>
                            <Label>Support Phone</Label>
                            <Input value={form.data.support_phone} onChange={e => form.setData('support_phone', e.target.value)} />
                        </div>
                        <div>
                            <Label>Footer Copyright Text</Label>
                            <Input value={form.data.footer_copyright} onChange={e => form.setData('footer_copyright', e.target.value)}
                                placeholder="© 2026 Genius SMS. All rights reserved." />
                        </div>
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing} className="gap-2">
                            <Save className="w-4 h-4" /> Save Platform Settings
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}

/* ── Payment Gateway ── */
function PaymentTab({ settings }: { settings: Record<string, string> }) {
    const [showSecret,  setShowSecret]  = useState(false);
    const [showWebhook, setShowWebhook] = useState(false);
    const form = useForm({
        stripe_key:       settings.stripe_key       ?? '',
        stripe_secret:    settings.stripe_secret    ?? '',
        stripe_webhook:   settings.stripe_webhook   ?? '',
        payment_currency: settings.payment_currency ?? 'USD',
    });
    const { flash } = usePage<any>().props;

    return (
        <form onSubmit={e => { e.preventDefault(); form.post('/super-admin/settings/payment'); }}>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Stripe Payment Gateway</CardTitle>
                    <CardDescription>Configure Stripe keys for subscription billing.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {flash?.success && <SuccessBanner message={flash.success} />}
                    <div>
                        <Label>Publishable Key (pk_live_…)</Label>
                        <Input value={form.data.stripe_key} onChange={e => form.setData('stripe_key', e.target.value)}
                            placeholder="pk_live_..." className="font-mono text-sm" />
                    </div>
                    <div>
                        <Label>Secret Key (sk_live_…)</Label>
                        <div className="relative">
                            <Input type={showSecret ? 'text' : 'password'}
                                value={form.data.stripe_secret} onChange={e => form.setData('stripe_secret', e.target.value)}
                                placeholder="sk_live_..." className="font-mono text-sm pr-10" />
                            <button type="button" onClick={() => setShowSecret(!showSecret)}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                {showSecret ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                            </button>
                        </div>
                    </div>
                    <div>
                        <Label>Webhook Secret (whsec_…)</Label>
                        <div className="relative">
                            <Input type={showWebhook ? 'text' : 'password'}
                                value={form.data.stripe_webhook} onChange={e => form.setData('stripe_webhook', e.target.value)}
                                placeholder="whsec_..." className="font-mono text-sm pr-10" />
                            <button type="button" onClick={() => setShowWebhook(!showWebhook)}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                {showWebhook ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                            </button>
                        </div>
                    </div>
                    <div className="w-40">
                        <Label>Billing Currency</Label>
                        <Select value={form.data.payment_currency} onValueChange={v => form.setData('payment_currency', v)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                {CURRENCIES.map(c => <SelectItem key={c} value={c}>{c}</SelectItem>)}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing} className="gap-2">
                            <Save className="w-4 h-4" /> Save Payment Settings
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}

/* ── Platform SMTP ── */
function SmtpTab({ settings }: { settings: Record<string, string> }) {
    const [showPass, setShowPass] = useState(false);
    const form = useForm({
        platform_mail_host:         settings.platform_mail_host         ?? '',
        platform_mail_port:         settings.platform_mail_port         ?? '587',
        platform_mail_username:     settings.platform_mail_username     ?? '',
        platform_mail_password:     settings.platform_mail_password     ?? '',
        platform_mail_encryption:   settings.platform_mail_encryption   ?? 'tls',
        platform_mail_from_address: settings.platform_mail_from_address ?? '',
        platform_mail_from_name:    settings.platform_mail_from_name    ?? '',
    });
    const { flash } = usePage<any>().props;

    return (
        <form onSubmit={e => { e.preventDefault(); form.post('/super-admin/settings/smtp'); }}>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Platform Email (SMTP)</CardTitle>
                    <CardDescription>Used for platform-level emails like invoices, subscription notices and password resets.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {flash?.success && <SuccessBanner message={flash.success} />}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div className="sm:col-span-2">
                            <Label>SMTP Host</Label>
                            <Input value={form.data.platform_mail_host} onChange={e => form.setData('platform_mail_host', e.target.value)}
                                placeholder="smtp.mailgun.org" />
                        </div>
                        <div>
                            <Label>Port</Label>
                            <Input value={form.data.platform_mail_port} onChange={e => form.setData('platform_mail_port', e.target.value)}
                                placeholder="587" />
                        </div>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <Label>Username</Label>
                            <Input value={form.data.platform_mail_username} onChange={e => form.setData('platform_mail_username', e.target.value)} />
                        </div>
                        <div>
                            <Label>Password</Label>
                            <div className="relative">
                                <Input type={showPass ? 'text' : 'password'}
                                    value={form.data.platform_mail_password}
                                    onChange={e => form.setData('platform_mail_password', e.target.value)}
                                    className="pr-10" />
                                <button type="button" onClick={() => setShowPass(!showPass)}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    {showPass ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <Label>Encryption</Label>
                            <Select value={form.data.platform_mail_encryption} onValueChange={v => form.setData('platform_mail_encryption', v as any)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="tls">TLS</SelectItem>
                                    <SelectItem value="ssl">SSL</SelectItem>
                                    <SelectItem value="none">None</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>From Address</Label>
                            <Input type="email" value={form.data.platform_mail_from_address}
                                onChange={e => form.setData('platform_mail_from_address', e.target.value)}
                                placeholder="no-reply@example.com" />
                        </div>
                        <div>
                            <Label>From Name</Label>
                            <Input value={form.data.platform_mail_from_name}
                                onChange={e => form.setData('platform_mail_from_name', e.target.value)}
                                placeholder="Genius SMS" />
                        </div>
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing} className="gap-2">
                            <Save className="w-4 h-4" /> Save SMTP Settings
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}

/* ── Localization ── */
function LocalizationTab({ settings }: { settings: Record<string, string> }) {
    const form = useForm({
        default_timezone:    settings.default_timezone    ?? 'UTC',
        default_currency:    settings.default_currency    ?? 'USD',
        default_date_format: settings.default_date_format ?? 'Y-m-d',
        default_language:    settings.default_language    ?? 'en',
    });
    const { flash } = usePage<any>().props;

    return (
        <form onSubmit={e => { e.preventDefault(); form.post('/super-admin/settings/localization'); }}>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Localization Defaults</CardTitle>
                    <CardDescription>Default locale settings applied when a new school is created.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {flash?.success && <SuccessBanner message={flash.success} />}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <Label>Default Timezone</Label>
                            <Select value={form.data.default_timezone} onValueChange={v => form.setData('default_timezone', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {TIMEZONES.map(tz => <SelectItem key={tz} value={tz}>{tz}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Default Currency</Label>
                            <Select value={form.data.default_currency} onValueChange={v => form.setData('default_currency', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {CURRENCIES.map(c => <SelectItem key={c} value={c}>{c}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Default Date Format</Label>
                            <Select value={form.data.default_date_format} onValueChange={v => form.setData('default_date_format', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {DATE_FORMATS.map(f => <SelectItem key={f} value={f}>{f}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Default Language</Label>
                            <Select value={form.data.default_language} onValueChange={v => form.setData('default_language', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {LANGUAGES.map(l => <SelectItem key={l.value} value={l.value}>{l.label}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing} className="gap-2">
                            <Save className="w-4 h-4" /> Save Localization
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}

/* ── Maintenance Mode ── */
function MaintenanceTab({ settings }: { settings: Record<string, string> }) {
    const isOn = settings.maintenance_mode === '1';
    const form = useForm({
        maintenance_mode:    isOn,
        maintenance_message: settings.maintenance_message ?? 'We are performing scheduled maintenance. We will be back shortly.',
    });
    const { flash } = usePage<any>().props;

    return (
        <form onSubmit={e => { e.preventDefault(); form.post('/super-admin/settings/maintenance'); }}>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Maintenance Mode</CardTitle>
                    <CardDescription>When enabled, all school users see a maintenance page instead of the application.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-5">
                    {flash?.success && <SuccessBanner message={flash.success} />}

                    {form.data.maintenance_mode && (
                        <div className="flex items-center gap-2 text-sm text-amber-700 bg-amber-50 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-2">
                            <AlertTriangle className="w-4 h-4 shrink-0" />
                            Maintenance mode is currently <strong>ON</strong>. School users cannot access the platform.
                        </div>
                    )}

                    <div className="flex items-center justify-between p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                        <div>
                            <p className="font-medium text-slate-900 dark:text-white text-sm">Enable Maintenance Mode</p>
                            <p className="text-xs text-slate-500 mt-0.5">Redirect all school users to a maintenance page</p>
                        </div>
                        <button type="button" onClick={() => form.setData('maintenance_mode', !form.data.maintenance_mode)}
                            className={cn(
                                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none',
                                form.data.maintenance_mode ? 'bg-red-500' : 'bg-slate-200 dark:bg-slate-700'
                            )}>
                            <span className={cn(
                                'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
                                form.data.maintenance_mode ? 'translate-x-6' : 'translate-x-1'
                            )} />
                        </button>
                    </div>

                    <div>
                        <Label>Maintenance Message</Label>
                        <Textarea rows={3} value={form.data.maintenance_message}
                            onChange={e => form.setData('maintenance_message', e.target.value)} />
                        <p className="text-xs text-slate-400 mt-1">This message is displayed to users when maintenance mode is active.</p>
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing}
                            className={cn('gap-2', form.data.maintenance_mode ? 'bg-red-600 hover:bg-red-700' : '')}>
                            <Save className="w-4 h-4" /> Save Maintenance Settings
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}

/* ── Storage & Upload Limits ── */
function StorageTab({ settings }: { settings: Record<string, string> }) {
    const form = useForm({
        upload_max_mb:        settings.upload_max_mb        ?? '10',
        storage_per_school_gb: settings.storage_per_school_gb ?? '5',
        allowed_extensions:   settings.allowed_extensions   ?? 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,zip',
    });
    const { flash } = usePage<any>().props;

    return (
        <form onSubmit={e => { e.preventDefault(); form.post('/super-admin/settings/storage'); }}>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Storage & Upload Limits</CardTitle>
                    <CardDescription>Control how much storage each school can use and what files they can upload.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-5">
                    {flash?.success && <SuccessBanner message={flash.success} />}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <Label>Max Upload Size (MB)</Label>
                            <Input type="number" min="1" max="500"
                                value={form.data.upload_max_mb}
                                onChange={e => form.setData('upload_max_mb', e.target.value)} />
                            <p className="text-xs text-slate-400 mt-1">Maximum size for a single file upload.</p>
                        </div>
                        <div>
                            <Label>Storage per School (GB)</Label>
                            <Input type="number" min="1" max="1000"
                                value={form.data.storage_per_school_gb}
                                onChange={e => form.setData('storage_per_school_gb', e.target.value)} />
                            <p className="text-xs text-slate-400 mt-1">Total cloud storage allocated per school.</p>
                        </div>
                    </div>
                    <div>
                        <Label>Allowed File Extensions</Label>
                        <Input value={form.data.allowed_extensions}
                            onChange={e => form.setData('allowed_extensions', e.target.value)}
                            placeholder="jpg,png,pdf,doc,docx" />
                        <p className="text-xs text-slate-400 mt-1">Comma-separated list. Leave blank to allow all.</p>
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing} className="gap-2">
                            <Save className="w-4 h-4" /> Save Storage Settings
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}

/* ── Notification Templates ── */
const TEMPLATES = [
    { key: 'welcome_school',      label: 'Welcome School',         desc: 'Sent when a new school is created.' },
    { key: 'subscription_expiry', label: 'Subscription Expiry',    desc: 'Sent 7 days before subscription expires.' },
    { key: 'trial_ending',        label: 'Trial Ending',           desc: 'Sent 3 days before trial ends.' },
    { key: 'invoice',             label: 'Payment Invoice',        desc: 'Sent after a successful payment.' },
    { key: 'password_reset',      label: 'Password Reset',         desc: 'Sent when a user requests a password reset.' },
];

const TEMPLATE_VARS: Record<string, string[]> = {
    welcome_school:      ['{{school_name}}', '{{admin_email}}', '{{login_url}}'],
    subscription_expiry: ['{{school_name}}', '{{plan_name}}', '{{expiry_date}}', '{{renew_url}}'],
    trial_ending:        ['{{school_name}}', '{{trial_end_date}}', '{{upgrade_url}}'],
    invoice:             ['{{school_name}}', '{{amount}}', '{{plan_name}}', '{{invoice_url}}'],
    password_reset:      ['{{user_name}}', '{{reset_url}}', '{{expires_in}}'],
};

function NotificationTemplatesTab({ settings }: { settings: Record<string, string> }) {
    const [activeTemplate, setActiveTemplate] = useState(TEMPLATES[0].key);
    const tpl = TEMPLATES.find(t => t.key === activeTemplate)!;

    const form = useForm({
        template_key: activeTemplate,
        subject:      settings[`tpl_${activeTemplate}_subject`] ?? '',
        body:         settings[`tpl_${activeTemplate}_body`]    ?? '',
    });
    const { flash } = usePage<any>().props;

    function switchTemplate(key: string) {
        setActiveTemplate(key);
        form.setData({
            template_key: key,
            subject:      settings[`tpl_${key}_subject`] ?? '',
            body:         settings[`tpl_${key}_body`]    ?? '',
        });
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Notification Templates</CardTitle>
                <CardDescription>Customize the email templates sent for platform events.</CardDescription>
            </CardHeader>
            <CardContent>
                {flash?.success && <SuccessBanner message={flash.success} />}
                <div className="flex gap-6 mt-2">
                    {/* Template list */}
                    <ul className="w-48 shrink-0 space-y-0.5">
                        {TEMPLATES.map(t => (
                            <li key={t.key}>
                                <button onClick={() => switchTemplate(t.key)} type="button"
                                    className={cn('w-full text-left px-3 py-2 rounded-lg text-sm transition-colors',
                                        activeTemplate === t.key
                                            ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 font-medium'
                                            : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'
                                    )}>
                                    {t.label}
                                </button>
                            </li>
                        ))}
                    </ul>

                    {/* Template editor */}
                    <form className="flex-1 space-y-4"
                        onSubmit={e => { e.preventDefault(); form.post('/super-admin/settings/templates'); }}>
                        <input type="hidden" value={form.data.template_key} readOnly />
                        <p className="text-xs text-slate-500">{tpl.desc}</p>
                        <div>
                            <Label>Subject</Label>
                            <Input value={form.data.subject}
                                onChange={e => form.setData('subject', e.target.value)}
                                placeholder={`${tpl.label} subject line…`} />
                        </div>
                        <div>
                            <Label>Body</Label>
                            <Textarea rows={8} value={form.data.body}
                                onChange={e => form.setData('body', e.target.value)}
                                placeholder="Email body…" className="font-mono text-sm" />
                        </div>
                        {/* Available variables */}
                        <div className="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3">
                            <p className="text-xs font-semibold text-slate-500 mb-2">Available Variables</p>
                            <div className="flex flex-wrap gap-1.5">
                                {(TEMPLATE_VARS[activeTemplate] ?? []).map(v => (
                                    <code key={v} className="text-[11px] bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 px-1.5 py-0.5 rounded text-indigo-600 dark:text-indigo-400">
                                        {v}
                                    </code>
                                ))}
                            </div>
                        </div>
                        <div className="flex justify-end">
                            <Button type="submit" disabled={form.processing} className="gap-2">
                                <Save className="w-4 h-4" /> Save Template
                            </Button>
                        </div>
                    </form>
                </div>
            </CardContent>
        </Card>
    );
}

/* ── Audit Log Retention ── */
function AuditLogTab({ settings }: { settings: Record<string, string> }) {
    const form = useForm({
        audit_retention_days: settings.audit_retention_days ?? '90',
        auto_purge_logs:      settings.auto_purge_logs === '1',
    });
    const { flash } = usePage<any>().props;

    const retentionOptions = [
        { value: '30',    label: '30 days' },
        { value: '60',    label: '60 days' },
        { value: '90',    label: '90 days (recommended)' },
        { value: '180',   label: '6 months' },
        { value: '365',   label: '1 year' },
        { value: '0',     label: 'Forever (no purge)' },
    ];

    return (
        <form onSubmit={e => { e.preventDefault(); form.post('/super-admin/settings/audit'); }}>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Audit Log Retention</CardTitle>
                    <CardDescription>Configure how long activity logs are kept in the database.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-5">
                    {flash?.success && <SuccessBanner message={flash.success} />}
                    <div className="w-72">
                        <Label>Retention Period</Label>
                        <Select value={form.data.audit_retention_days} onValueChange={v => form.setData('audit_retention_days', v)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                {retentionOptions.map(o => <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>)}
                            </SelectContent>
                        </Select>
                        <p className="text-xs text-slate-400 mt-1">Logs older than this will be eligible for purging.</p>
                    </div>

                    <div className="flex items-center justify-between p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                        <div>
                            <p className="font-medium text-slate-900 dark:text-white text-sm">Auto-Purge Old Logs</p>
                            <p className="text-xs text-slate-500 mt-0.5">Automatically delete logs older than the retention period (runs nightly)</p>
                        </div>
                        <button type="button" onClick={() => form.setData('auto_purge_logs', !form.data.auto_purge_logs)}
                            className={cn(
                                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none',
                                form.data.auto_purge_logs ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-slate-700'
                            )}>
                            <span className={cn(
                                'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
                                form.data.auto_purge_logs ? 'translate-x-6' : 'translate-x-1'
                            )} />
                        </button>
                    </div>

                    {form.data.audit_retention_days !== '0' && form.data.auto_purge_logs && (
                        <div className="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg text-xs text-amber-700 dark:text-amber-400">
                            <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5" />
                            <p>Auto-purge is enabled. Logs older than <strong>{retentionOptions.find(o => o.value === form.data.audit_retention_days)?.label}</strong> will be permanently deleted every night.</p>
                        </div>
                    )}

                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing} className="gap-2">
                            <Save className="w-4 h-4" /> Save Audit Settings
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}

/* ── Main Page ── */
export default function SuperAdminSettings({ settings, logoUrl, faviconUrl }: Props) {
    const [activeTab, setActiveTab] = useState<TabId>('general');

    return (
        <AppLayout title="Platform Settings">
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Platform Settings</h1>
                    <p className="text-sm text-slate-500 mt-0.5">Configure global platform behaviour and integrations</p>
                </div>

                <div className="flex gap-6">
                    {/* Tab sidebar */}
                    <nav className="w-52 shrink-0">
                        <ul className="space-y-0.5">
                            {TABS.map(t => (
                                <li key={t.id}>
                                    <button onClick={() => setActiveTab(t.id)}
                                        className={cn(
                                            'w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors text-left',
                                            activeTab === t.id
                                                ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300'
                                                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'
                                        )}>
                                        <t.icon className="w-4 h-4 shrink-0" />
                                        {t.label}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </nav>

                    {/* Tab content */}
                    <div className="flex-1 min-w-0">
                        {activeTab === 'general'      && <GeneralTab settings={settings} logoUrl={logoUrl} faviconUrl={faviconUrl} />}
                        {activeTab === 'payment'      && <PaymentTab settings={settings} />}
                        {activeTab === 'smtp'         && <SmtpTab settings={settings} />}
                        {activeTab === 'localization' && <LocalizationTab settings={settings} />}
                        {activeTab === 'storage'      && <StorageTab settings={settings} />}
                        {activeTab === 'templates'    && <NotificationTemplatesTab settings={settings} />}
                        {activeTab === 'audit'        && <AuditLogTab settings={settings} />}
                        {activeTab === 'maintenance'  && <MaintenanceTab settings={settings} />}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
