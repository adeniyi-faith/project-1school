import type { ElementType } from 'react';
import {
    LayoutDashboard, School, Users, GraduationCap, UserCog, CalendarDays, BookOpen, ClipboardList, Banknote, Library, Bus, Home, Package, MessageSquare, BarChart3, Settings, ChevronLeft, ChevronRight, Layers, Clock, CalendarOff, Building2, BadgeCheck, NotebookPen, Video, Megaphone, Mail, Send, Bell, PieChart, FileText, TrendingUp, Wrench, ShieldCheck, Plug, CreditCard, Tag, CalendarRange, SlidersHorizontal, ClipboardCheck, Gift, ArrowUpRight, Palette, MessageSquareQuote, Printer, Award, History, MonitorCheck, CalendarClock, IdCard, ScanLine, Medal, Shapes, Sparkles, Globe, Newspaper, Images, Activity,
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
    /** Rarely-used groups start folded. A group holding the open page always shows. */
    folded?: boolean;
    /** Roles for whom this group starts folded (others see it open). */
    foldedFor?: string[];
}

export const navGroups: NavGroup[] = [
    {
        title: 'System',
        items: [
            { label: 'Dashboard',   href: '/school/reports/dashboard',  icon: LayoutDashboard, roles: ['school-admin','principal','teacher','accountant','librarian'] },
            { label: 'My Classes',  href: '/school/my-classes',         icon: GraduationCap,   roles: ['teacher'] },
            { label: 'Home',        href: '/school/home',               icon: LayoutDashboard, roles: ['receptionist','driver','warden','store-manager'] },
            { label: 'Dashboard',   href: '/super-admin/dashboard',     icon: LayoutDashboard, roles: ['super-admin'], exact: true },
            { label: 'My Dashboard',href: '/school/student/dashboard',  icon: LayoutDashboard, roles: ['student'] },
            { label: 'My Dashboard',href: '/school/parent/dashboard',   icon: LayoutDashboard, roles: ['parent'] },
            { label: 'Schools',     href: '/super-admin/schools',       icon: School,          roles: ['super-admin'] },
        ],
    },
    {
        title: 'School Setup',
        folded: true,
        items: [
            { label: 'Start a New Term', href: '/school/new-term', icon: CalendarRange, roles: ['super-admin','school-admin','principal'] },
            { label: 'Classes',     href: '/school/classes',  icon: GraduationCap,  roles: ['super-admin','school-admin','principal'] },
            { label: 'Arms',        href: '/school/sections', icon: Layers,         roles: ['super-admin','school-admin','principal'] },
            { label: 'Subjects',    href: '/school/subjects', icon: BookOpen,       roles: ['super-admin','school-admin','principal'] },
            { label: 'Shifts',      href: '/school/shifts',   icon: Clock,          roles: ['super-admin','school-admin','principal'] },
            { label: 'Holidays',    href: '/school/holidays', icon: CalendarOff,    roles: ['super-admin','school-admin','principal'] },
            { label: 'Terms',       href: '/school/academics/terms',      icon: CalendarRange, roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Scores & Grades', href: '/school/academics/assessment', icon: SlidersHorizontal, roles: ['super-admin','school-admin','principal'] },
            { label: 'Report Card Designs', href: '/school/academics/report-card-designs', icon: Palette, roles: ['super-admin','school-admin','principal'] },
        ],
    },
    {
        title: 'Academic',
        items: [
            { label: 'Students',    href: '/school/students', icon: GraduationCap, roles: ['super-admin','school-admin','principal','teacher','receptionist','accountant'] },
            { label: 'Staff',       href: '/school/staff',   icon: UserCog,        roles: ['super-admin','school-admin','principal'] },
            { label: 'Timetable',   href: '/school/timetable', icon: CalendarDays,  roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Attendance',  href: '/school/attendance', icon: ClipboardList, roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Scan ID Card', href: '/school/id-cards/scan',       icon: ScanLine,       roles: ['super-admin','school-admin','principal','teacher','receptionist','warden','accountant'] },
        ],
    },
    {
        title: 'Results',
        items: [
            { label: 'Class Test Marks',href: '/school/exams',    icon: BookOpen,       roles: ['super-admin','school-admin','principal','teacher','accountant'] },
            { label: 'Term Results', href: '/school/results',             icon: ClipboardCheck, roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Print Report Cards', href: '/school/results/print', icon: Printer, roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Comment Bank', href: '/school/results/comment-bank', icon: MessageSquareQuote, roles: ['super-admin','school-admin','principal','teacher'] },
        ],
    },
    {
        title: 'Student Records',
        folded: true,
        items: [
            { label: 'Move Students Up', href: '/school/promotions',     icon: ArrowUpRight,   roles: ['super-admin','school-admin'] },
            { label: 'Certificates', href: '/school/certificates',       icon: Award,          roles: ['super-admin','school-admin','principal'] },
            { label: 'ID Cards',    href: '/school/id-cards',             icon: IdCard,         roles: ['super-admin','school-admin','principal'], exact: true },
            { label: 'Houses',      href: '/school/houses',               icon: Medal,          roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Clubs',       href: '/school/clubs',                icon: Shapes,         roles: ['super-admin','school-admin','principal','teacher'] },
        ],
    },
    {
        title: 'HR Setup',
        folded: true,
        items: [
            { label: 'Departments',  href: '/school/departments',  icon: Building2,   roles: ['super-admin','school-admin','principal'] },
            { label: 'Designations', href: '/school/designations', icon: BadgeCheck,  roles: ['super-admin','school-admin','principal'] },
            { label: 'Leave Requests', href: '/school/hr/leaves', icon: CalendarDays, roles: ['super-admin','school-admin','principal'] },
            { label: 'Payroll',      href: '/school/hr/payroll',   icon: Banknote,  roles: ['super-admin','school-admin','accountant'] },
        ],
    },
    {
        title: 'Finance',
        foldedFor: ['principal'],
        items: [
            { label: 'Invoices',       href: '/school/fees/invoices',   icon: FileText,  roles: ['super-admin','school-admin','accountant','principal'] },
            { label: 'Scholarships',   href: '/school/fees/scholarships', icon: Gift,    roles: ['super-admin','school-admin','accountant'] },
            { label: 'Online Payments', href: '/school/fees/online-payments', icon: CreditCard, roles: ['super-admin','school-admin','accountant'] },
            { label: 'Payment Plans', href: '/school/fees/payment-plans', icon: CalendarClock, roles: ['super-admin','school-admin','accountant'] },
            { label: 'Fee Structures', href: '/school/fees/structures',  icon: BarChart3,   roles: ['super-admin','school-admin','accountant'] },
            { label: 'Fee Categories', href: '/school/fees/categories',  icon: ClipboardList, roles: ['super-admin','school-admin','accountant'] },
        ],
    },
    {
        title: 'Learning',
        foldedFor: ['principal'],
        items: [
            { label: 'Homework',      href: '/school/homework',                icon: NotebookPen,   roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Lesson Notes',  href: '/school/lesson-notes',             icon: ClipboardList, roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Syllabus',      href: '/school/homework/syllabi',        icon: BookOpen,      roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Online Classes',href: '/school/homework/online-classes', icon: Video,         roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Computer Tests', href: '/school/cbt/tests',               icon: MonitorCheck,  roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Question Banks',href: '/school/cbt/banks',               icon: Library,       roles: ['super-admin','school-admin','principal','teacher'] },
            { label: 'Printed Papers', href: '/school/cbt/papers',              icon: Printer,       roles: ['super-admin','school-admin','principal','teacher'] },
        ],
    },
    {
        title: 'My Academic',
        items: [
            { label: 'My Timetable',  href: '/school/student/timetable',  icon: CalendarDays,   roles: ['student'] },
            { label: 'My Attendance', href: '/school/student/attendance',  icon: ClipboardList,  roles: ['student'] },
            { label: 'My Results',    href: '/school/student/results',     icon: BarChart3,      roles: ['student'] },
            { label: 'My Homework',   href: '/school/student/homework',    icon: NotebookPen,    roles: ['student'] },
            { label: 'My Tests',      href: '/school/student/cbt',         icon: MonitorCheck,   roles: ['student'] },
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
            { label: 'Fees',          href: '/school/parent/fees',         icon: Banknote,    roles: ['parent'] },
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
        foldedFor: ['principal'],
        items: [
            { label: 'Inquiries', href: '/school/admissions/inquiries', icon: ClipboardList, roles: ['super-admin','school-admin','principal','receptionist'] },
            { label: 'Visitors',  href: '/school/admissions/visitors',  icon: Users,         roles: ['super-admin','school-admin','principal','receptionist'] },
        ],
    },
    {
        title: 'Facilities',
        folded: true,
        items: [
            { label: 'Library',     href: '/school/library/books',       icon: Library,  roles: ['super-admin','school-admin','principal','librarian'] },
            { label: 'Transport',   href: '/school/transport/vehicles',  icon: Bus,      roles: ['super-admin','school-admin','driver'] },
            { label: 'Hostel',      href: '/school/hostel',              icon: Home,     roles: ['super-admin','school-admin','warden'] },
            { label: 'Inventory',   href: '/school/inventory/items',     icon: Package,  roles: ['super-admin','school-admin','store-manager'] },
        ],
    },
    {
        title: 'Communication',
        foldedFor: ['teacher', 'principal'],
        items: [
            { label: 'Announcements',    href: '/school/communication/announcements',   icon: Megaphone,     roles: ['super-admin','school-admin','principal','teacher','receptionist','driver','warden','store-manager'] },
            { label: 'Messages',         href: '/school/communication/messages',         icon: MessageSquare, roles: ['super-admin','school-admin','principal','teacher','accountant','librarian','receptionist'] },
            { label: 'SMS/Email Blast',  href: '/school/communication/blast',            icon: Send,          roles: ['super-admin','school-admin','principal'] },
            { label: 'Sent Messages',    href: '/school/communication/sent',             icon: History,       roles: ['super-admin','school-admin','principal'] },
            { label: 'Email Templates',  href: '/school/communication/email-templates',  icon: Mail,          roles: ['super-admin','school-admin'] },
            { label: 'Notifications',    href: '/school/communication/notifications',    icon: Bell,          roles: ['super-admin','school-admin','principal','teacher','accountant','librarian','receptionist','driver','warden','store-manager'] },
        ],
    },
    {
        title: 'History',
        folded: true,
        items: [
            { label: 'Old Payment Records', href: '/school/fees/payments', icon: Banknote,  roles: ['super-admin','school-admin','accountant'] },
        ],
    },
    {
        title: 'Reports',
        foldedFor: ['teacher'],
        items: [
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
            { label: 'Newsletter Email', href: '/super-admin/newsletter',    icon: Mail,        roles: ['super-admin'] },
            { label: 'Site Extensions', href: '/super-admin/extensions',    icon: Plug,        roles: ['super-admin'] },
        ],
    },
    {
        title: 'Website',
        folded: true,
        items: [
            { label: 'Overview',      href: '/school/website',          icon: Globe,     roles: ['school-admin'], exact: true },
            { label: 'Pages',         href: '/school/website/pages',    icon: FileText,  roles: ['school-admin'] },
            { label: 'News & events', href: '/school/website/posts',    icon: Newspaper, roles: ['school-admin'] },
            { label: 'Themes',        href: '/school/website/themes',   icon: Palette,   roles: ['school-admin'] },
            { label: 'Pictures',      href: '/school/website/media',    icon: Images,    roles: ['school-admin'] },
            { label: 'Extensions',    href: '/school/website/extensions', icon: Plug,    roles: ['school-admin'] },
            { label: 'Site settings', href: '/school/website/settings', icon: SlidersHorizontal, roles: ['school-admin'] },
        ],
    },
    {
        title: 'Developer tools',
        folded: true,
        items: [
            { label: 'Platform Health', href: '/super-admin/health', icon: Activity, roles: ['super-admin'] },
        ],
    },
    {
        title: 'Admin',
        folded: true,
        items: [
            { label: 'Settings',      href: '/school/settings',              icon: Settings, roles: ['school-admin'], exact: true },
            { label: 'Settings',      href: '/super-admin/settings',         icon: Settings, roles: ['super-admin'] },
            { label: 'School Type',   href: '/school/settings/school-type',  icon: School,   roles: ['school-admin'] },
            { label: 'AI Assistant',  href: '/school/settings/ai',           icon: Sparkles, roles: ['school-admin'] },
            { label: 'AI (Gemini)',   href: '/super-admin/ai',               icon: Sparkles, roles: ['super-admin'] },
            { label: 'Integrations',  href: '/school/settings/integrations', icon: Plug,     roles: ['super-admin','school-admin'] },
            { label: 'Manage Users',  href: '/school/settings/admins',       icon: UserCog,  roles: ['school-admin'] },
            { label: 'All Users',     href: '/super-admin/users',            icon: Users,    roles: ['super-admin'] },
            { label: 'Platform Staff', href: '/super-admin/staff',           icon: UserCog,  roles: ['super-admin'] },
            { label: 'Staff Activity', href: '/super-admin/audit',           icon: History,  roles: ['super-admin'] },
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
        { label: 'My Classes', href: '/school/my-classes',        icon: GraduationCap },
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
    receptionist: [
        { label: 'Home',       href: '/school/home',                 icon: LayoutDashboard },
        { label: 'Visitors',   href: '/school/admissions/visitors',  icon: Users },
        { label: 'Scan card',  href: '/school/id-cards/scan',        icon: ScanLine },
        { label: 'Students',   href: '/school/students',             icon: GraduationCap },
    ],
    driver: [
        { label: 'Home',       href: '/school/home',                 icon: LayoutDashboard },
        { label: 'Routes',     href: '/school/transport/routes',     icon: Bus },
        { label: 'Vehicles',   href: '/school/transport/vehicles',   icon: Bus },
        { label: 'Notices',    href: '/school/communication/announcements', icon: Megaphone },
    ],
    warden: [
        { label: 'Home',       href: '/school/home',                 icon: LayoutDashboard },
        { label: 'Hostels',    href: '/school/hostel',               icon: Home },
        { label: 'Rooms',      href: '/school/hostel/allocations',   icon: ClipboardList },
        { label: 'Notices',    href: '/school/communication/announcements', icon: Megaphone },
    ],
    'store-manager': [
        { label: 'Home',       href: '/school/home',                 icon: LayoutDashboard },
        { label: 'Items',      href: '/school/inventory/items',      icon: Package },
        { label: 'Issues',     href: '/school/inventory/issues',     icon: ClipboardList },
        { label: 'Assets',     href: '/school/inventory/assets',     icon: Layers },
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
    // Platform staff: the tabs are cut down to the pages their role may open
    'platform-staff': [
        { label: 'Home',          href: '/super-admin/dashboard',     icon: LayoutDashboard, exact: true },
        { label: 'Schools',       href: '/super-admin/schools',       icon: School },
        { label: 'Subscriptions', href: '/super-admin/subscriptions', icon: CreditCard },
        { label: 'Users',         href: '/super-admin/users',         icon: Users },
    ],
    'super-admin': [
        { label: 'Home',          href: '/super-admin/dashboard',     icon: LayoutDashboard, exact: true },
        { label: 'Schools',       href: '/super-admin/schools',       icon: School },
        { label: 'Subscriptions', href: '/super-admin/subscriptions', icon: CreditCard },
        { label: 'Users',         href: '/super-admin/users',         icon: Users },
    ],
};

/**
 * The menu for one person. `paths` (from the server) says which pages they may really open;
 * when it is missing the role list is used as a fallback.
 */
export function navGroupsForRole(role: string, paths?: string[], actingSchool = false): NavGroup[] {
    const inPaths = (item: NavItem) => !paths || paths.includes(item.href);
    // The platform owner sees the platform menu, or, once inside a school, that school's whole menu
    const allowed = (item: NavItem) => {
        if (role === 'super-admin' || role === 'platform-staff') {
            const school = item.href.startsWith('/school');
            const ok = actingSchool ? school && (!item.roles || item.roles.includes('super-admin') || item.roles.includes('school-admin')) : item.href.startsWith('/super-admin');
            // The platform's own pages are always listed for the owner; school pages, and everything for platform staff, follow what the person may open
            return ok && (actingSchool || role === 'platform-staff' ? inPaths(item) : true);
        }
        return (!item.roles || item.roles.includes(role)) && inPaths(item);
    };

    return navGroups
        .map((group) => ({ ...group, items: group.items.filter(allowed) }))
        .filter((group) => group.items.length > 0);
}

/** Phone tab-bar shortcuts, leaving out any the person cannot open */
export function tabsForRole(role: string, paths?: string[], actingSchool = false): BottomTab[] {
    const tabs = bottomTabs[role === 'super-admin' && actingSchool ? 'school-admin' : role] ?? bottomTabs.default;

    // The platform owner's own tabs never depend on the page list
    if (role === 'super-admin' && !actingSchool) return tabs;

    return paths ? tabs.filter((t) => paths.includes(t.href)) : tabs;
}

/**
 * Everyday words people type into the menu search, matched to the page that does the job.
 * The page's own name is always searched too.
 */
export const searchKeywords: Record<string, string> = {
    '/school/students': 'pupils admit enrol enroll register child children add student find student profile',
    '/school/students/import': 'upload excel csv bulk many students',
    '/school/fees/invoices': 'collect fee collect payment take payment receive money pay cash bill create invoices debtors owing owe outstanding balance',
    '/school/fees/structures': 'school fees amount tuition price set fees',
    '/school/fees/scholarships': 'discount waiver bursary free place',
    '/school/fees/payment-plans': 'instalment installment pay in parts',
    '/school/fees/online-payments': 'paystack flutterwave monnify remita card pay online',
    '/school/results': 'scores marks grades enter scores ca exam results publish approve',
    '/school/results/print': 'print report cards report card download pdf zip transcript',
    '/school/results/comment-bank': 'remarks comments report card comment',
    '/school/promotions': 'promote promotion move up next class graduate repeat end of year',
    '/school/academics/terms': 'new term session school year start term current term',
    '/school/academics/assessment': 'grading grade scale ca1 ca2 score parts',
    '/school/new-term': 'new term checklist resumption beginning of term',
    '/school/attendance': 'register roll call absent present mark attendance',
    '/school/staff': 'teachers employees workers add teacher add staff',
    '/school/hr/leaves': 'leave holiday off sick absence request',
    '/school/hr/payroll': 'salary salaries payslip pay staff',
    '/school/certificates': 'testimonial transfer certificate leaving certificate',
    '/school/id-cards': 'identity card id card print cards',
    '/school/communication/blast': 'sms text message email everyone parents bulk',
    '/school/communication/announcements': 'notice news tell parents',
    '/school/communication/messages': 'inbox chat write message',
    '/school/admissions/inquiries': 'admission enquiry new applicant prospective entrance exam interview offer',
    '/school/admissions/visitors': 'sign in visitor gate guest',
    '/school/timetable': 'lessons periods schedule',
    '/school/lesson-notes': 'scheme of work lesson plan ai',
    '/school/cbt/tests': 'computer test online test exam cbt',
    '/school/settings': 'school name logo address colours',
    '/school/settings/admins': 'users logins passwords accounts roles permissions',
    '/school/settings/school-type': 'british american montessori nigerian curriculum',
    '/school/reports/finance': 'money income collected report',
    '/school/library/books': 'books borrow lend loan',
    '/school/classes': 'form grade level add class',
    '/school/sections': 'divisions stream a b c',
};

/** Jobs that start a task straight away. Each shows only when the person may open the page it leads to. */
export const searchActions: { label: string; href: string; keywords: string }[] = [
    { label: 'Add a student', href: '/school/students/create', keywords: 'admit enrol enroll new pupil register child' },
    { label: 'Add many students from Excel', href: '/school/students/import', keywords: 'upload bulk import csv' },
    { label: 'Add a staff member', href: '/school/staff/create', keywords: 'new teacher employee hire' },
    { label: 'Collect a fee payment', href: '/school/fees/invoices', keywords: 'take payment receive money cash bursar' },
    { label: 'Print report cards', href: '/school/results/print', keywords: 'download pdf zip' },
    { label: 'Send an SMS or email to parents', href: '/school/communication/blast', keywords: 'text message bulk' },
];

export function isNavActive(url: string, href: string, exact?: boolean): boolean {
    const path = url.split('?')[0];
    return exact ? path === href : path === href || path.startsWith(href + '/');
}
