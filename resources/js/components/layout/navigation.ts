import type { ElementType } from 'react';
import {
    LayoutDashboard, School, Users, GraduationCap, UserCog,
    CalendarDays, BookOpen, ClipboardList, Banknote,
    Library, Bus, Home, Package, MessageSquare, BarChart3,
    Settings, ChevronLeft, ChevronRight, Layers, Clock, CalendarOff,
    Building2, BadgeCheck, NotebookPen, Video, Megaphone, Mail, Send, Bell,
    PieChart, FileText, TrendingUp, Wrench, ShieldCheck, Plug,
    CreditCard, Tag, CalendarRange, SlidersHorizontal, ClipboardCheck, Gift, ArrowUpRight, Palette, MessageSquareQuote, Printer, Award,
} from 'lucide-react';

export interface NavItem {
    label: string;
    href: string;
    icon: ElementType;
    roles?: string[];
    exact?: boolean;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export const navGroups: NavGroup[] = [
    {
        title: 'System',
        items: [
            { label: 'Dashboard',   href: '/school/reports/dashboard',  icon: LayoutDashboard, roles: ['school-admin','principal','teacher','accountant','librarian'] },
            { label: 'Dashboard',   href: '/super-admin/dashboard',     icon: LayoutDashboard, roles: ['super-admin'], exact: true },
            { label: 'My Dashboard',href: '/school/student/dashboard',  icon: LayoutDashboard, roles: ['student'] },
            { label: 'My Dashboard',href: '/school/parent/dashboard',   icon: LayoutDashboard, roles: ['parent'] },
            { label: 'Schools',     href: '/super-admin/schools',       icon: School,          roles: ['super-admin'] },
        ],
    },
    {
        title: 'School Setup',
        items: [
            { label: 'Classes',     href: '/school/classes',  icon: GraduationCap,  roles: ['super-admin','school-admin','principal'] },
            { label: 'Sections',    href: '/school/sections', icon: Layers,         roles: ['super-admin','school-admin','principal'] },
            { label: 'Subjects',    href: '/school/subjects', icon: BookOpen,       roles: ['super-admin','school-admin','principal'] },
            { label: 'Shifts',      href: '/school/shifts',   icon: Clock,          roles: ['super-admin','school-admin','principal'] },
            { label: 'Holidays',    href: '/school/holidays', icon: CalendarOff,    roles: ['super-admin','school-admin','principal'] },
        ],
    },
    {
        title: 'Academic',
        items: [
            { label: 'Students',    href: '/school/students', icon: GraduationCap, roles: ['super-admin','school-admin','principal','teacher','receptionist','accountant'] },
            { label: 'Staff',       href: '/school/staff',   icon: UserCog,        roles: ['super-admin','school-admin','principal'] },
            { label: 'Timetable',   href: '/school/timetable', icon: CalendarDays,  roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Attendance',  href: '/school/attendance', icon: ClipboardList, roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Examinations',href: '/school/exams',    icon: BookOpen,       roles: ['super-admin','school-admin','principal','teacher','accountant'] },
            { label: 'Move Students Up', href: '/school/promotions',     icon: ArrowUpRight,   roles: ['super-admin','school-admin'] },
            { label: 'Certificates', href: '/school/certificates',       icon: Award,          roles: ['super-admin','school-admin','principal'] },
            { label: 'Term Results', href: '/school/results',             icon: ClipboardCheck, roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Terms',       href: '/school/academics/terms',      icon: CalendarRange, roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Scores & Grades', href: '/school/academics/assessment', icon: SlidersHorizontal, roles: ['super-admin','school-admin','principal'] },
            { label: 'Print Report Cards', href: '/school/results/print', icon: Printer, roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Comment Bank', href: '/school/results/comment-bank', icon: MessageSquareQuote, roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Report Card Designs', href: '/school/academics/report-card-designs', icon: Palette, roles: ['super-admin','school-admin','principal'] },
        ],
    },
    {
        title: 'HR Setup',
        items: [
            { label: 'Departments',  href: '/school/departments',  icon: Building2,   roles: ['super-admin','school-admin','principal'] },
            { label: 'Designations', href: '/school/designations', icon: BadgeCheck,  roles: ['super-admin','school-admin','principal'] },
            { label: 'Leave Requests', href: '/school/hr/leaves', icon: CalendarDays, roles: ['super-admin','school-admin','principal'] },
            { label: 'Payroll',      href: '/school/hr/payroll',   icon: Banknote,  roles: ['super-admin','school-admin','accountant'] },
        ],
    },
    {
        title: 'Finance',
        items: [
            { label: 'Invoices',       href: '/school/fees/invoices',   icon: FileText,  roles: ['super-admin','school-admin','accountant','principal'] },
            { label: 'Scholarships',   href: '/school/fees/scholarships', icon: Gift,    roles: ['super-admin','school-admin','accountant'] },
            { label: 'Old Payment Records', href: '/school/fees/payments', icon: Banknote,  roles: ['super-admin','school-admin','accountant'] },
            { label: 'Fee Structures', href: '/school/fees/structures',  icon: BarChart3,   roles: ['super-admin','school-admin','accountant'] },
            { label: 'Fee Categories', href: '/school/fees/categories',  icon: ClipboardList, roles: ['super-admin','school-admin','accountant'] },
        ],
    },
    {
        title: 'Learning',
        items: [
            { label: 'Homework',      href: '/school/homework',                icon: NotebookPen,   roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Lesson Plans',  href: '/school/homework/lesson-plans',   icon: ClipboardList, roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Syllabus',      href: '/school/homework/syllabi',        icon: BookOpen,      roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Online Classes',href: '/school/homework/online-classes', icon: Video,         roles: ['super-admin','school-admin','principal','teacher'] },
        ],
    },
    {
        title: 'My Academic',
        items: [
            { label: 'My Timetable',  href: '/school/student/timetable',  icon: CalendarDays,   roles: ['student'] },
            { label: 'My Attendance', href: '/school/student/attendance',  icon: ClipboardList,  roles: ['student'] },
            { label: 'My Results',    href: '/school/student/results',     icon: BarChart3,      roles: ['student'] },
            { label: 'My Homework',   href: '/school/student/homework',    icon: NotebookPen,    roles: ['student'] },
        ],
    },
    {
        title: 'My Finance',
        items: [
            { label: 'My Fees',       href: '/school/student/fees',        icon: Banknote,    roles: ['student'] },
        ],
    },
    {
        title: 'My Children',
        items: [
            { label: 'Attendance',    href: '/school/parent/attendance',   icon: ClipboardList, roles: ['parent'] },
            { label: 'Results',       href: '/school/parent/results',      icon: BarChart3,     roles: ['parent'] },
            { label: 'Fee Status',    href: '/school/parent/fees',         icon: Banknote,    roles: ['parent'] },
        ],
    },
    {
        title: 'School Info',
        items: [
            { label: 'Announcements', href: '/school/student/announcements', icon: Megaphone,   roles: ['student'] },
            { label: 'Announcements', href: '/school/parent/announcements',  icon: Megaphone,   roles: ['parent'] },
        ],
    },
    {
        title: 'Admissions',
        items: [
            { label: 'Inquiries', href: '/school/admissions/inquiries', icon: ClipboardList, roles: ['super-admin','school-admin','principal','receptionist'] },
            { label: 'Visitors',  href: '/school/admissions/visitors',  icon: Users,         roles: ['super-admin','school-admin','principal','receptionist'] },
        ],
    },
    {
        title: 'Facilities',
        items: [
            { label: 'Library',     href: '/school/library/books',       icon: Library,  roles: ['super-admin','school-admin','principal','librarian'] },
            { label: 'Transport',   href: '/school/transport/vehicles',  icon: Bus,      roles: ['super-admin','school-admin','driver'] },
            { label: 'Hostel',      href: '/school/hostel',              icon: Home,     roles: ['super-admin','school-admin','warden'] },
            { label: 'Inventory',   href: '/school/inventory/items',     icon: Package,  roles: ['super-admin','school-admin','store-manager'] },
        ],
    },
    {
        title: 'Communication',
        items: [
            { label: 'Announcements',    href: '/school/communication/announcements',   icon: Megaphone,     roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Messages',         href: '/school/communication/messages',         icon: MessageSquare, roles: ['super-admin','school-admin','principal','teacher','accountant','librarian'] },
            { label: 'SMS/Email Blast',  href: '/school/communication/blast',            icon: Send,          roles: ['super-admin','school-admin','principal'] },
            { label: 'Email Templates',  href: '/school/communication/email-templates',  icon: Mail,          roles: ['super-admin','school-admin'] },
            { label: 'Notifications',    href: '/school/communication/notifications',    icon: Bell,          roles: ['super-admin','school-admin','principal','teacher','accountant','librarian'] },
        ],
    },
    {
        title: 'Reports',
        items: [
            { label: 'Dashboard',        href: '/school/reports/dashboard',  icon: PieChart,    roles: ['super-admin','school-admin','principal','teacher','accountant'] },
            { label: 'Attendance',       href: '/school/reports/attendance', icon: ClipboardList,roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Academic',         href: '/school/reports/academic',   icon: TrendingUp,  roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Finance',          href: '/school/reports/finance',    icon: Banknote,  roles: ['super-admin','school-admin','accountant'] },
            { label: 'Custom Report',    href: '/school/reports/custom',     icon: FileText,    roles: ['super-admin','school-admin','principal','accountant'] },
            { label: 'Audit Log',        href: '/school/reports/audit-log',  icon: ShieldCheck, roles: ['super-admin','school-admin'] },
        ],
    },
    {
        title: 'Subscription',
        items: [
            { label: 'Packages',       href: '/super-admin/packages',       icon: Package,     roles: ['super-admin'] },
            { label: 'Subscriptions',  href: '/super-admin/subscriptions',  icon: CreditCard,  roles: ['super-admin'] },
            { label: 'Coupons',        href: '/super-admin/coupons',        icon: Tag,         roles: ['super-admin'] },
            { label: 'Module Manager', href: '/super-admin/module-manager', icon: Layers,      roles: ['super-admin'] },
        ],
    },
    {
        title: 'Admin',
        items: [
            { label: 'Settings',      href: '/school/settings',              icon: Settings, roles: ['school-admin'] },
            { label: 'Settings',      href: '/super-admin/settings',         icon: Settings, roles: ['super-admin'] },
            { label: 'Integrations',  href: '/school/settings/integrations', icon: Plug,     roles: ['super-admin','school-admin'] },
            { label: 'Manage Users',  href: '/school/settings/admins',       icon: UserCog,  roles: ['school-admin'] },
            { label: 'All Users',     href: '/super-admin/users',            icon: Users,    roles: ['super-admin'] },
        ],
    },
];


export interface BottomTab {
    label: string;
    href: string;
    icon: ElementType;
    exact?: boolean;
}

/** The four shortcuts shown in the phone tab bar for each role. A fifth "More" tab opens the full menu. */
export const bottomTabs: Record<string, BottomTab[]> = {
    default: [
        { label: 'Home',       href: '/school/reports/dashboard', icon: LayoutDashboard },
        { label: 'Students',   href: '/school/students',          icon: GraduationCap },
        { label: 'Attendance', href: '/school/attendance',        icon: ClipboardList },
        { label: 'Fees',       href: '/school/fees/invoices',     icon: Banknote },
    ],
    teacher: [
        { label: 'Home',       href: '/school/reports/dashboard', icon: LayoutDashboard },
        { label: 'Attendance', href: '/school/attendance',        icon: ClipboardList },
        { label: 'Exams',      href: '/school/exams',             icon: BookOpen },
        { label: 'Timetable',  href: '/school/timetable',         icon: CalendarDays },
    ],
    accountant: [
        { label: 'Home',       href: '/school/reports/dashboard', icon: LayoutDashboard },
        { label: 'Invoices',   href: '/school/fees/invoices',     icon: Banknote },
        { label: 'Students',   href: '/school/students',          icon: GraduationCap },
        { label: 'Reports',    href: '/school/reports/finance',   icon: BarChart3 },
    ],
    librarian: [
        { label: 'Home',       href: '/school/reports/dashboard', icon: LayoutDashboard },
        { label: 'Library',    href: '/school/library/books',     icon: Library },
        { label: 'Messages',   href: '/school/communication/messages', icon: MessageSquare },
        { label: 'Alerts',     href: '/school/communication/notifications', icon: Bell },
    ],
    parent: [
        { label: 'Home',       href: '/school/parent/dashboard',  icon: LayoutDashboard },
        { label: 'Attendance', href: '/school/parent/attendance', icon: ClipboardList },
        { label: 'Results',    href: '/school/parent/results',    icon: BarChart3 },
        { label: 'Fees',       href: '/school/parent/fees',       icon: Banknote },
    ],
    student: [
        { label: 'Home',       href: '/school/student/dashboard', icon: LayoutDashboard },
        { label: 'Timetable',  href: '/school/student/timetable', icon: CalendarDays },
        { label: 'Results',    href: '/school/student/results',   icon: BarChart3 },
        { label: 'Homework',   href: '/school/student/homework',  icon: NotebookPen },
    ],
    'super-admin': [
        { label: 'Home',          href: '/super-admin/dashboard',     icon: LayoutDashboard, exact: true },
        { label: 'Schools',       href: '/super-admin/schools',       icon: School },
        { label: 'Subscriptions', href: '/super-admin/subscriptions', icon: CreditCard },
        { label: 'Users',         href: '/super-admin/users',         icon: Users },
    ],
};

export function navGroupsForRole(role: string): NavGroup[] {
    return navGroups
        .map((group) => ({ ...group, items: group.items.filter((item) => !item.roles || item.roles.includes(role)) }))
        .filter((group) => group.items.length > 0);
}

export function isNavActive(url: string, href: string, exact?: boolean): boolean {
    const path = url.split('?')[0];
    return exact ? path === href : path === href || path.startsWith(href + '/');
}
