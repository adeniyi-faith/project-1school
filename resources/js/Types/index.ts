export interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    avatar: string | null;
    role: string | null;
    school_id: number | null;
    status: string;
    last_login_at: string | null;
}

export interface School {
    id: number;
    name: string;
    slug: string;
    logo: string | null;
    logo_url: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    country: string;
    timezone: string;
    currency: string;
    language: string;
    status: 'active' | 'inactive' | 'suspended';
    users_count?: number;
    created_at: string;
    updated_at: string;
}

export interface AcademicYear {
    id: number;
    school_id: number;
    name: string;
    start_date: string;
    end_date: string;
    is_current: boolean;
}

export interface PageProps {
    auth: {
        user: User | null;
    };
    school: School | null;
    flash: {
        success?: string;
        error?: string;
        info?: string;
    };
    faviconUrl: string | null;
    errors: Record<string, string>;
}

export interface Guardian {
    id: number;
    school_id: number;
    user_id: number | null;
    name: string;
    relation: string;
    phone: string | null;
    email: string | null;
    occupation: string | null;
    address: string | null;
    photo: string | null;
}

export interface StudentDocument {
    id: number;
    student_id: number;
    title: string;
    file_path: string;
    file_url: string;
    file_type: string | null;
    file_size: number | null;
    created_at: string;
}

export interface Student {
    id: number;
    school_id: number;
    class_id: number;
    section_id: number | null;
    guardian_id: number | null;
    admission_no: string;
    roll_no: string | null;
    first_name: string;
    last_name: string | null;
    full_name: string;
    gender: 'male' | 'female' | 'other';
    date_of_birth: string | null;
    blood_group: string | null;
    religion: string | null;
    nationality: string;
    phone: string | null;
    email: string | null;
    address: string | null;
    photo: string | null;
    photo_url: string | null;
    category: 'general' | 'disabled' | 'quota';
    status: 'active' | 'alumni' | 'transferred' | 'inactive';
    admission_date: string | null;
    previous_school: string | null;
    created_at: string;
    school_class?: SchoolClass;
    section?: Section;
    guardian?: Guardian;
    documents?: StudentDocument[];
}

export interface SchoolClass {
    id: number;
    school_id: number;
    name: string;
    numeric_name: number | null;
    capacity: number;
    class_teacher_id: number | null;
    sections_count?: number;
    subjects_count?: number;
    sections?: Section[];
    subjects?: Subject[];
}

export interface Section {
    id: number;
    school_id: number;
    class_id: number;
    name: string;
    capacity: number;
    school_class?: SchoolClass;
}

export interface Subject {
    id: number;
    school_id: number;
    class_id: number;
    name: string;
    code: string | null;
    type: 'theory' | 'practical';
    full_marks: number;
    pass_marks: number;
    school_class?: SchoolClass;
}

export interface Shift {
    id: number;
    school_id: number;
    name: string;
    start_time: string;
    end_time: string;
}

export interface Holiday {
    id: number;
    school_id: number;
    name: string;
    date: string;
    description: string | null;
}

export type DayOfWeek = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday';

export interface Timetable {
    id: number;
    school_id: number;
    class_id: number;
    section_id: number | null;
    subject_id: number;
    teacher_id: number | null;
    day_of_week: DayOfWeek;
    start_time: string;
    end_time: string;
    room: string | null;
    notes: string | null;
    subject?: Subject;
    teacher?: Staff;
    school_class?: SchoolClass;
    section?: Section;
}

export interface TimeSlot {
    start: string;
    end: string;
}

export interface Attendance {
    id: number;
    school_id: number;
    date: string;
    attendable_type: string;
    attendable_id: number;
    status: 'present' | 'absent' | 'late' | 'half_day';
    remarks: string | null;
}

export interface Department {
    id: number;
    school_id: number;
    name: string;
    code: string | null;
    description: string | null;
    staff_count?: number;
}

export interface Designation {
    id: number;
    school_id: number;
    department_id: number | null;
    name: string;
    description: string | null;
    staff_count?: number;
    department?: Department;
}

export interface StaffDocument {
    id: number;
    staff_id: number;
    title: string;
    file_path: string;
    file_url: string;
    file_type: string | null;
    file_size: number | null;
    created_at: string;
}

export interface Staff {
    id: number;
    school_id: number;
    user_id: number | null;
    department_id: number | null;
    designation_id: number | null;
    emp_id: string;
    first_name: string;
    last_name: string | null;
    full_name: string;
    gender: 'male' | 'female' | 'other';
    date_of_birth: string | null;
    blood_group: string | null;
    religion: string | null;
    nationality: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    photo: string | null;
    photo_url: string | null;
    joining_date: string | null;
    salary_type: 'fixed' | 'hourly';
    salary: string | null;
    status: 'active' | 'resigned' | 'terminated' | 'on_leave';
    notes: string | null;
    created_at: string;
    department?: Department;
    designation?: Designation;
    documents?: StaffDocument[];
}

export type PaginatedResponse<T> = {
    data: T[];
    meta: {
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
    };
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
};

// ───────────── School years, terms, score setups and grade scales ─────────────

export interface Term {
    id: number;
    name: string;
    sequence: number;
    start_date: string | null;
    end_date: string | null;
    is_current: boolean;
}

export interface AcademicYearWithTerms {
    id: number;
    name: string;
    start_date: string | null;
    end_date: string | null;
    is_current: boolean;
    terms: Term[];
}

/** A term as offered in a dropdown, e.g. "2026/2027 · First Term" */
export interface TermOption {
    id: number;
    label: string;
    is_current: boolean;
}

/** One score part, e.g. CA1 worth 20 marks */
export type AssessmentComponent = {
    id?: number;
    name: string;
    short_name: string;
    max_score: number;
};

export interface AssessmentScheme {
    id: number;
    name: string;
    is_default: boolean;
    components: AssessmentComponent[];
}

/** One grade in a grade scale, e.g. A1 from 75 to 100 */
export type GradeBand = {
    id?: number;
    grade: string;
    min_marks: number;
    max_marks: number;
    remarks: string | null;
    gpa: number;
};

export interface GradingScheme {
    id: number;
    name: string;
    is_default: boolean;
    bands: GradeBand[];
}

export interface AssessmentPresets {
    assessment: { key: string; name: string; components: AssessmentComponent[] }[];
    grading: { key: string; name: string; bands: GradeBand[] }[];
}

// ───────────── Term results ─────────────

export type ResultStatus = 'draft' | 'submitted' | 'approved' | 'published' | 'locked';
export type ResultAction = 'submit' | 'approve' | 'return' | 'publish' | 'unpublish' | 'lock' | 'unlock';

export interface BehaviourTrait {
    id: number;
    name: string;
    domain: 'affective' | 'psychomotor';
}

/** A published term result as parents and students see it */
export interface TermReport {
    id: number;
    term: string;
    class: string | null;
    average: number;
    total_score: number;
    position: number | null;
    class_size: number;
    class_average: number | null;
    subjects: { subject: string | null; total: number; grade: string | null; remarks: string | null; position: number | null; average: number | null }[];
    ratings: { name: string | null; domain: string | null; rating: number; label: string | null }[];
}

// ───────────── Invoices, ledger and scholarships ─────────────

export type InvoiceStatus = 'unpaid' | 'partial' | 'paid' | 'void';
export type LedgerType = 'charge' | 'fine' | 'payment' | 'discount' | 'reversal';
export type PaymentMethod = 'cash' | 'bank_transfer' | 'pos' | 'card' | 'online' | 'ussd';

export interface InvoiceRow {
    id: number;
    invoice_no: string;
    student: { id: number; name: string; admission_no: string; class: string | null } | null;
    fee: string;
    period: string;
    amount: number;
    balance: number;
    status: InvoiceStatus;
    due_date: string | null;
}

export interface InvoiceDetail extends InvoiceRow {
    void_reason: string | null;
    guardian: { name: string; phone: string | null } | null;
    net_paid: number;
}

/** One line of an invoice's record. Positive adds to what is owed; negative takes it away. */
export interface LedgerLine {
    id: number;
    type: LedgerType;
    amount: number;
    method: PaymentMethod | null;
    reference: string | null;
    entry_date: string | null;
    note: string | null;
    recorded_by: string | null;
    reverses_id: number | null;
    reversed: boolean;
}

export interface Scholarship {
    id: number;
    name: string;
    type: 'percent' | 'fixed';
    value: number;
    fee_category_id: number | null;
    fee_category: string | null;
    description: string | null;
    is_active: boolean;
    holders: number;
}

export interface ScholarshipAward {
    id: number;
    student: { name: string; admission_no: string; class: string | null } | null;
    scholarship: string | null;
    approved_by: string | null;
    approved_at: string | null;
    note: string | null;
    active: boolean;
}
