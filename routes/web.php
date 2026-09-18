<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SchoolAdmin\AttendanceController;
use App\Http\Controllers\SchoolAdmin\ExamController;
use App\Http\Controllers\SchoolAdmin\FeeCategoryController;
use App\Http\Controllers\SchoolAdmin\FeePaymentController;
use App\Http\Controllers\SchoolAdmin\FeeStructureController;
use App\Http\Controllers\SchoolAdmin\CommunicationController;
use App\Http\Controllers\SchoolAdmin\IntegrationController;
use App\Http\Controllers\SchoolAdmin\ReportController;
use App\Http\Controllers\SchoolAdmin\HomeworkController;
use App\Http\Controllers\SchoolAdmin\LeaveController;
use App\Http\Controllers\SchoolAdmin\AssetController;
use App\Http\Controllers\SchoolAdmin\HostelController;
use App\Http\Controllers\SchoolAdmin\TransportController;
use App\Http\Controllers\SchoolAdmin\InventoryController;
use App\Http\Controllers\SchoolAdmin\LibraryController;
use App\Http\Controllers\SchoolAdmin\PayrollController;
use App\Http\Controllers\SchoolAdmin\TimetableController;
use App\Http\Controllers\SchoolAdmin\ClassController;
use App\Http\Controllers\SchoolAdmin\DepartmentController;
use App\Http\Controllers\SchoolAdmin\DesignationController;
use App\Http\Controllers\SchoolAdmin\HolidayController;
use App\Http\Controllers\SchoolAdmin\SectionController;
use App\Http\Controllers\SchoolAdmin\ShiftController;
use App\Http\Controllers\SchoolAdmin\StaffController;
use App\Http\Controllers\SchoolAdmin\StudentController;
use App\Http\Controllers\SchoolAdmin\SubjectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolAdmin\SchoolUserController;
use App\Http\Controllers\SchoolAdmin\SettingsController as SchoolSettingsController;
use App\Http\Controllers\SuperAdmin\SettingsController as SuperAdminSettingsController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SchoolAdmin\AdmissionInquiryController;
use App\Http\Controllers\SchoolAdmin\VisitorLogController;
use App\Http\Controllers\PublicAdmissionController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\SuperAdmin\CouponController;
use App\Http\Controllers\SuperAdmin\ModuleManagerController;
use App\Http\Controllers\SuperAdmin\PackageController;
use App\Http\Controllers\SuperAdmin\SchoolController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\UserManagementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect()->route('login'));
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Profile
    Route::get('/profile',                   [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile',                   [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/password/change',           [ProfileController::class, 'changePasswordPage'])->name('password.change');
    Route::put('/profile/password',          [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/dashboard', function () {
        $user = auth()->user();

        // Redirect to role-specific dashboard
        return match (true) {
            $user->hasRole('super-admin')                                   => redirect()->route('super-admin.dashboard'),
            $user->hasRole('school-admin') || $user->hasRole('principal')   => redirect()->route('school.reports.dashboard'),
            $user->hasRole('teacher')                                        => redirect()->route('school.reports.dashboard'),
            $user->hasRole('student')                                        => redirect()->route('student.dashboard'),
            $user->hasRole('parent')                                         => redirect()->route('parent.dashboard'),
            default                                                          => redirect()->route('school.reports.dashboard'),
        };
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | School Admin routes (school-admin, principal)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super-admin|school-admin|principal|teacher|accountant|librarian')
        ->prefix('school')
        ->name('school.')
        ->group(function () {
            Route::resource('classes',  ClassController::class)->except(['create', 'edit', 'show']);
            Route::resource('sections', SectionController::class)->except(['create', 'edit', 'show']);
            Route::resource('subjects', SubjectController::class)->except(['create', 'edit', 'show']);
            Route::resource('shifts',   ShiftController::class)->except(['create', 'edit', 'show']);
            Route::resource('holidays', HolidayController::class)->except(['create', 'edit', 'show']);
            Route::resource('students', StudentController::class);
            Route::post('students/{student}/documents',        [StudentController::class, 'uploadDocument'])->name('students.documents.upload');
            Route::delete('students/documents/{document}',     [StudentController::class, 'deleteDocument'])->name('students.documents.delete');

            // Exams
            Route::get('exams',                              [ExamController::class, 'index'])->name('exams.index');
            Route::post('exams',                             [ExamController::class, 'store'])->name('exams.store');
            Route::put('exams/{exam}',                       [ExamController::class, 'update'])->name('exams.update');
            Route::delete('exams/{exam}',                    [ExamController::class, 'destroy'])->name('exams.destroy');
            Route::get('exams/{exam}/marks',                 [ExamController::class, 'marks'])->name('exams.marks');
            Route::post('exams/{exam}/marks',                [ExamController::class, 'saveMarks'])->name('exams.marks.save');
            Route::get('exams/{exam}/results',               [ExamController::class, 'results'])->name('exams.results');
            Route::get('grade-scales',                       [ExamController::class, 'gradeScales'])->name('grade-scales.index');
            Route::post('grade-scales',                      [ExamController::class, 'saveGradeScale'])->name('grade-scales.store');
            Route::put('grade-scales/{gradeScale}',          [ExamController::class, 'updateGradeScale'])->name('grade-scales.update');
            Route::delete('grade-scales/{gradeScale}',       [ExamController::class, 'deleteGradeScale'])->name('grade-scales.destroy');

            // Timetable
            Route::get('timetable',                      [TimetableController::class, 'index'])->name('timetable.index');
            Route::post('timetable',                     [TimetableController::class, 'store'])->name('timetable.store');
            Route::delete('timetable/{timetable}',       [TimetableController::class, 'destroy'])->name('timetable.destroy');
            Route::get('timetable/teacher',              [TimetableController::class, 'teacherSchedule'])->name('timetable.teacher');

            // Attendance — student
            Route::get('attendance',                          [AttendanceController::class, 'index'])->name('attendance.index');
            Route::post('attendance',                         [AttendanceController::class, 'store'])->name('attendance.store');
            Route::get('attendance/students/{student}/calendar', [AttendanceController::class, 'studentCalendar'])->name('attendance.student.calendar');
            // Attendance — staff
            Route::get('attendance/staff',                    [AttendanceController::class, 'staffIndex'])->name('attendance.staff.index');
            Route::post('attendance/staff',                   [AttendanceController::class, 'staffStore'])->name('attendance.staff.store');

            // HR — Leave Management
            Route::get('hr/leave-types',                       [LeaveController::class, 'types'])->name('hr.leave-types.index');
            Route::post('hr/leave-types',                      [LeaveController::class, 'storeType'])->name('hr.leave-types.store');
            Route::put('hr/leave-types/{leaveType}',           [LeaveController::class, 'updateType'])->name('hr.leave-types.update');
            Route::delete('hr/leave-types/{leaveType}',        [LeaveController::class, 'destroyType'])->name('hr.leave-types.destroy');
            Route::get('hr/leaves',                            [LeaveController::class, 'index'])->name('hr.leaves.index');
            Route::post('hr/leaves',                           [LeaveController::class, 'store'])->name('hr.leaves.store');
            Route::put('hr/leaves/{leaveRequest}/approve',     [LeaveController::class, 'approve'])->name('hr.leaves.approve');

            // HR — Payroll
            Route::get('hr/salary-structure',                  [PayrollController::class, 'structure'])->name('hr.salary-structure.index');
            Route::put('hr/salary-structure/{staff}',          [PayrollController::class, 'saveStructure'])->name('hr.salary-structure.save');
            Route::get('hr/payroll',                           [PayrollController::class, 'index'])->name('hr.payroll.index');
            Route::post('hr/payroll/generate',                 [PayrollController::class, 'generate'])->name('hr.payroll.generate');
            Route::put('hr/payroll/{payroll}/paid',            [PayrollController::class, 'markPaid'])->name('hr.payroll.paid');
            Route::get('hr/payroll/{payroll}/slip',            [PayrollController::class, 'slip'])->name('hr.payroll.slip');

            // Library Management
            Route::get('library/books',                        [LibraryController::class, 'index'])->name('library.books.index');
            Route::post('library/books',                       [LibraryController::class, 'store'])->name('library.books.store');
            Route::put('library/books/{book}',                 [LibraryController::class, 'update'])->name('library.books.update');
            Route::delete('library/books/{book}',              [LibraryController::class, 'destroy'])->name('library.books.destroy');
            Route::get('library/issues',                       [LibraryController::class, 'issues'])->name('library.issues.index');
            Route::post('library/issues',                      [LibraryController::class, 'issueBook'])->name('library.issues.store');
            Route::put('library/issues/{bookIssue}/return',    [LibraryController::class, 'returnBook'])->name('library.issues.return');
            Route::get('library/overdue',                      [LibraryController::class, 'overdue'])->name('library.overdue');

            // Inventory Management
            Route::get('inventory/categories',                         [InventoryController::class, 'categories'])->name('inventory.categories');
            Route::post('inventory/categories',                        [InventoryController::class, 'storeCategory'])->name('inventory.categories.store');
            Route::put('inventory/categories/{inventoryCategory}',     [InventoryController::class, 'updateCategory'])->name('inventory.categories.update');
            Route::delete('inventory/categories/{inventoryCategory}',  [InventoryController::class, 'destroyCategory'])->name('inventory.categories.destroy');

            Route::get('inventory/items',                              [InventoryController::class, 'items'])->name('inventory.items');
            Route::post('inventory/items',                             [InventoryController::class, 'storeItem'])->name('inventory.items.store');
            Route::put('inventory/items/{inventoryItem}',              [InventoryController::class, 'updateItem'])->name('inventory.items.update');
            Route::delete('inventory/items/{inventoryItem}',           [InventoryController::class, 'destroyItem'])->name('inventory.items.destroy');

            Route::get('inventory/purchases',                          [InventoryController::class, 'purchases'])->name('inventory.purchases');
            Route::post('inventory/purchases',                         [InventoryController::class, 'storePurchase'])->name('inventory.purchases.store');

            Route::get('inventory/issues',                             [InventoryController::class, 'issues'])->name('inventory.issues');
            Route::post('inventory/issues',                            [InventoryController::class, 'storeIssue'])->name('inventory.issues.store');
            Route::put('inventory/issues/{inventoryIssue}/return',     [InventoryController::class, 'returnIssue'])->name('inventory.issues.return');

            // Asset Management
            Route::get('inventory/assets',                             [AssetController::class, 'index'])->name('inventory.assets');
            Route::post('inventory/assets',                            [AssetController::class, 'store'])->name('inventory.assets.store');
            Route::get('inventory/assets/{asset}',                     [AssetController::class, 'show'])->name('inventory.assets.show');
            Route::put('inventory/assets/{asset}',                     [AssetController::class, 'update'])->name('inventory.assets.update');
            Route::delete('inventory/assets/{asset}',                  [AssetController::class, 'destroy'])->name('inventory.assets.destroy');
            Route::post('inventory/assets/{asset}/maintenance',        [AssetController::class, 'storeMaintenance'])->name('inventory.assets.maintenance');

            // Hostel Management
            Route::get('hostel',                                          [HostelController::class, 'index'])->name('hostel.index');
            Route::post('hostel',                                         [HostelController::class, 'store'])->name('hostel.store');
            Route::put('hostel/{hostel}',                                 [HostelController::class, 'update'])->name('hostel.update');
            Route::delete('hostel/{hostel}',                              [HostelController::class, 'destroy'])->name('hostel.destroy');

            Route::get('hostel/{hostel}/rooms',                           [HostelController::class, 'rooms'])->name('hostel.rooms');
            Route::post('hostel/{hostel}/rooms',                          [HostelController::class, 'storeRoom'])->name('hostel.rooms.store');
            Route::put('hostel/{hostel}/rooms/{room}',                    [HostelController::class, 'updateRoom'])->name('hostel.rooms.update');
            Route::delete('hostel/{hostel}/rooms/{room}',                 [HostelController::class, 'destroyRoom'])->name('hostel.rooms.destroy');
            Route::get('hostel/{hostel}/available-rooms',                 [HostelController::class, 'hostelRooms'])->name('hostel.available-rooms');

            Route::get('hostel/allocations',                              [HostelController::class, 'allocations'])->name('hostel.allocations');
            Route::post('hostel/allocations',                             [HostelController::class, 'storeAllocation'])->name('hostel.allocations.store');
            Route::put('hostel/allocations/{allocation}/vacate',          [HostelController::class, 'vacate'])->name('hostel.vacate');

            // Transport Management
            Route::get('transport/vehicles',                          [TransportController::class, 'vehicles'])->name('transport.vehicles');
            Route::post('transport/vehicles',                         [TransportController::class, 'storeVehicle'])->name('transport.vehicles.store');
            Route::put('transport/vehicles/{vehicle}',                [TransportController::class, 'updateVehicle'])->name('transport.vehicles.update');
            Route::delete('transport/vehicles/{vehicle}',             [TransportController::class, 'destroyVehicle'])->name('transport.vehicles.destroy');

            Route::get('transport/routes',                            [TransportController::class, 'routes'])->name('transport.routes');
            Route::post('transport/routes',                           [TransportController::class, 'storeRoute'])->name('transport.routes.store');
            Route::put('transport/routes/{route}',                    [TransportController::class, 'updateRoute'])->name('transport.routes.update');
            Route::delete('transport/routes/{route}',                 [TransportController::class, 'destroyRoute'])->name('transport.routes.destroy');

            Route::get('transport/routes/{route}/assignments',        [TransportController::class, 'assignments'])->name('transport.assignments');
            Route::post('transport/routes/{route}/assign',            [TransportController::class, 'assignStudent'])->name('transport.assign');
            Route::delete('transport/routes/{route}/students/{student}', [TransportController::class, 'removeStudent'])->name('transport.unassign');

            // Homework & Lesson Planning
            Route::get('homework',                                    [HomeworkController::class, 'index'])->name('homework.index');
            Route::post('homework',                                   [HomeworkController::class, 'store'])->name('homework.store');
            Route::put('homework/{homework}',                         [HomeworkController::class, 'update'])->name('homework.update');
            Route::delete('homework/{homework}',                      [HomeworkController::class, 'destroy'])->name('homework.destroy');
            Route::get('homework/{homework}/submissions',             [HomeworkController::class, 'submissions'])->name('homework.submissions');
            Route::put('homework/submissions/{submission}/review',    [HomeworkController::class, 'reviewSubmission'])->name('homework.submissions.review');

            Route::get('homework/lesson-plans',                       [HomeworkController::class, 'lessonPlans'])->name('homework.lesson-plans.index');
            Route::post('homework/lesson-plans',                      [HomeworkController::class, 'storeLessonPlan'])->name('homework.lesson-plans.store');
            Route::put('homework/lesson-plans/{lessonPlan}/review',   [HomeworkController::class, 'reviewLessonPlan'])->name('homework.lesson-plans.review');
            Route::delete('homework/lesson-plans/{lessonPlan}',       [HomeworkController::class, 'destroyLessonPlan'])->name('homework.lesson-plans.destroy');

            Route::get('homework/syllabi',                            [HomeworkController::class, 'syllabi'])->name('homework.syllabi.index');
            Route::post('homework/syllabi',                           [HomeworkController::class, 'storeSyllabus'])->name('homework.syllabi.store');
            Route::put('homework/syllabi/{syllabus}',                 [HomeworkController::class, 'updateSyllabus'])->name('homework.syllabi.update');

            Route::get('homework/online-classes',                     [HomeworkController::class, 'onlineClasses'])->name('homework.online-classes.index');
            Route::post('homework/online-classes',                    [HomeworkController::class, 'storeOnlineClass'])->name('homework.online-classes.store');
            Route::put('homework/online-classes/{onlineClass}/status',[HomeworkController::class, 'updateOnlineClassStatus'])->name('homework.online-classes.status');
            Route::delete('homework/online-classes/{onlineClass}',    [HomeworkController::class, 'destroyOnlineClass'])->name('homework.online-classes.destroy');

            // Fee Management
            Route::get('fees/categories',                    [FeeCategoryController::class, 'index'])->name('fees.categories.index');
            Route::post('fees/categories',                   [FeeCategoryController::class, 'store'])->name('fees.categories.store');
            Route::put('fees/categories/{feeCategory}',      [FeeCategoryController::class, 'update'])->name('fees.categories.update');
            Route::delete('fees/categories/{feeCategory}',   [FeeCategoryController::class, 'destroy'])->name('fees.categories.destroy');

            Route::get('fees/structures',                    [FeeStructureController::class, 'index'])->name('fees.structures.index');
            Route::post('fees/structures',                   [FeeStructureController::class, 'store'])->name('fees.structures.store');
            Route::put('fees/structures/{feeStructure}',     [FeeStructureController::class, 'update'])->name('fees.structures.update');
            Route::delete('fees/structures/{feeStructure}',  [FeeStructureController::class, 'destroy'])->name('fees.structures.destroy');

            Route::get('fees/payments',                      [FeePaymentController::class, 'index'])->name('fees.payments.index');
            Route::get('fees/payments/collect',              [FeePaymentController::class, 'create'])->name('fees.payments.create');
            Route::post('fees/payments',                     [FeePaymentController::class, 'store'])->name('fees.payments.store');
            Route::get('fees/payments/{feePayment}',         [FeePaymentController::class, 'show'])->name('fees.payments.show');
            Route::get('fees/outstanding',                   [FeePaymentController::class, 'outstanding'])->name('fees.outstanding');

            // Communication
            Route::get('communication/announcements',                              [CommunicationController::class, 'announcements'])->name('communication.announcements');
            Route::post('communication/announcements',                             [CommunicationController::class, 'storeAnnouncement'])->name('communication.announcements.store');
            Route::put('communication/announcements/{announcement}',               [CommunicationController::class, 'updateAnnouncement'])->name('communication.announcements.update');
            Route::delete('communication/announcements/{announcement}',            [CommunicationController::class, 'destroyAnnouncement'])->name('communication.announcements.destroy');

            Route::get('communication/messages',                                   [CommunicationController::class, 'messages'])->name('communication.messages');
            Route::post('communication/messages',                                  [CommunicationController::class, 'sendMessage'])->name('communication.messages.send');
            Route::put('communication/messages/{message}/read',                    [CommunicationController::class, 'readMessage'])->name('communication.messages.read');

            Route::get('communication/blast',                                      [CommunicationController::class, 'blast'])->name('communication.blast');
            Route::post('communication/blast',                                     [CommunicationController::class, 'sendBlast'])->name('communication.blast.send');

            Route::get('communication/email-templates',                            [CommunicationController::class, 'emailTemplates'])->name('communication.email-templates');
            Route::post('communication/email-templates',                           [CommunicationController::class, 'storeEmailTemplate'])->name('communication.email-templates.store');
            Route::put('communication/email-templates/{emailTemplate}',            [CommunicationController::class, 'updateEmailTemplate'])->name('communication.email-templates.update');

            Route::get('communication/notifications',                              [CommunicationController::class, 'notifications'])->name('communication.notifications');
            Route::put('communication/notifications/{notification}/read',          [CommunicationController::class, 'markNotificationRead'])->name('communication.notifications.read');
            Route::put('communication/notifications/read-all',                     [CommunicationController::class, 'markAllNotificationsRead'])->name('communication.notifications.read-all');

            // Reports & Analytics
            Route::get('reports/dashboard',                     [ReportController::class, 'dashboard'])->name('reports.dashboard');
            Route::get('reports/attendance',                    [ReportController::class, 'attendance'])->name('reports.attendance');
            Route::get('reports/academic',                      [ReportController::class, 'academic'])->name('reports.academic');
            Route::get('reports/finance',                       [ReportController::class, 'finance'])->name('reports.finance');
            Route::get('reports/custom',                        [ReportController::class, 'customBuilder'])->name('reports.custom');
            Route::post('reports/custom/run',                   [ReportController::class, 'runCustomReport'])->name('reports.custom.run');
            Route::get('reports/custom/export-csv',             [ReportController::class, 'exportCsv'])->name('reports.custom.csv');
            Route::get('reports/attendance/export-pdf',         [ReportController::class, 'exportAttendancePdf'])->name('reports.attendance.pdf');
            Route::get('reports/finance/export-pdf',            [ReportController::class, 'exportFinancePdf'])->name('reports.finance.pdf');
            Route::get('reports/audit-log',                     [ReportController::class, 'auditLog'])->name('reports.audit-log');

            // General / Branding / Academic / Notification Settings
            Route::get('settings',                              [SchoolSettingsController::class, 'index'])->name('settings.index');
            Route::post('settings/general',                     [SchoolSettingsController::class, 'saveGeneral'])->name('settings.general');
            Route::post('settings/branding',                    [SchoolSettingsController::class, 'saveBranding'])->name('settings.branding');
            Route::post('settings/academic',                    [SchoolSettingsController::class, 'saveAcademic'])->name('settings.academic');
            Route::post('settings/notifications',               [SchoolSettingsController::class, 'saveNotifications'])->name('settings.notifications');

            // Integrations / Gateway Settings
            Route::get('settings/integrations',                 [IntegrationController::class, 'index'])->name('settings.integrations');
            Route::post('settings/integrations/smtp',           [IntegrationController::class, 'saveSmtp'])->name('settings.integrations.smtp');
            Route::post('settings/integrations/smtp/test',      [IntegrationController::class, 'testSmtp'])->name('settings.integrations.smtp.test');
            Route::post('settings/integrations/sms',            [IntegrationController::class, 'saveSms'])->name('settings.integrations.sms');
            Route::post('settings/integrations/sms/test',       [IntegrationController::class, 'testSms'])->name('settings.integrations.sms.test');

            // School User / Admin Management
            Route::get('settings/admins',                       [SchoolUserController::class, 'index'])->name('settings.admins');
            Route::post('settings/admins',                      [SchoolUserController::class, 'store'])->name('settings.admins.store');
            Route::put('settings/admins/{user}',                [SchoolUserController::class, 'update'])->name('settings.admins.update');
            Route::delete('settings/admins/{user}',             [SchoolUserController::class, 'destroy'])->name('settings.admins.destroy');
            Route::patch('settings/admins/{user}/suspend',      [SchoolUserController::class, 'suspend'])->name('settings.admins.suspend');
            Route::patch('settings/admins/{user}/activate',     [SchoolUserController::class, 'activate'])->name('settings.admins.activate');

            // Admission Inquiries
            Route::get('admissions/inquiries',                          [AdmissionInquiryController::class, 'index'])->name('admissions.inquiries');
            Route::post('admissions/inquiries',                         [AdmissionInquiryController::class, 'store'])->name('admissions.inquiries.store');
            Route::put('admissions/inquiries/{admissionInquiry}',       [AdmissionInquiryController::class, 'update'])->name('admissions.inquiries.update');
            Route::delete('admissions/inquiries/{admissionInquiry}',    [AdmissionInquiryController::class, 'destroy'])->name('admissions.inquiries.destroy');
            Route::post('admissions/inquiries/{admissionInquiry}/followup', [AdmissionInquiryController::class, 'addFollowup'])->name('admissions.inquiries.followup');

            // Visitor Logs
            Route::get('admissions/visitors',                [VisitorLogController::class, 'index'])->name('admissions.visitors');
            Route::post('admissions/visitors',               [VisitorLogController::class, 'store'])->name('admissions.visitors.store');
            Route::patch('admissions/visitors/{visitorLog}/checkout', [VisitorLogController::class, 'checkout'])->name('admissions.visitors.checkout');
            Route::delete('admissions/visitors/{visitorLog}', [VisitorLogController::class, 'destroy'])->name('admissions.visitors.destroy');

            Route::resource('departments',  DepartmentController::class)->except(['create', 'edit', 'show']);
            Route::resource('designations', DesignationController::class)->except(['create', 'edit', 'show']);
            Route::resource('staff',        StaffController::class);
            Route::post('staff/{staff}/documents',         [StaffController::class, 'uploadDocument'])->name('staff.documents.upload');
            Route::delete('staff/documents/{document}',    [StaffController::class, 'deleteDocument'])->name('staff.documents.delete');
        });

    /*
    |--------------------------------------------------------------------------
    | Student & Parent portal routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:student')->prefix('school/student')->name('student.')->group(function () {
        Route::get('dashboard',     [StudentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('timetable',     [StudentPortalController::class, 'timetable'])->name('timetable');
        Route::get('attendance',    [StudentPortalController::class, 'attendance'])->name('attendance');
        Route::get('results',       [StudentPortalController::class, 'results'])->name('results');
        Route::get('homework',      [StudentPortalController::class, 'homework'])->name('homework');
        Route::get('fees',          [StudentPortalController::class, 'fees'])->name('fees');
        Route::get('announcements', [StudentPortalController::class, 'announcements'])->name('announcements');
    });

    Route::middleware('role:parent')->prefix('school/parent')->name('parent.')->group(function () {
        Route::get('dashboard',     [ParentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('attendance',    [ParentPortalController::class, 'attendance'])->name('attendance');
        Route::get('results',       [ParentPortalController::class, 'results'])->name('results');
        Route::get('fees',          [ParentPortalController::class, 'fees'])->name('fees');
        Route::get('announcements', [ParentPortalController::class, 'announcements'])->name('announcements');
    });

    /*
    |--------------------------------------------------------------------------
    | Super Admin routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super-admin')
        ->prefix('super-admin')
        ->name('super-admin.')
        ->group(function () {
            Route::resource('schools', SchoolController::class);
            Route::patch('schools/{school}/suspend', [SchoolController::class, 'suspend'])->name('schools.suspend');
            Route::patch('schools/{school}/activate', [SchoolController::class, 'activate'])->name('schools.activate');

            // User Management
            // Packages
            // Dashboard
            Route::get('dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');

            // Platform Settings
            Route::get('settings',                          [SuperAdminSettingsController::class, 'index'])->name('settings.index');
            Route::post('settings/general',                 [SuperAdminSettingsController::class, 'saveGeneral'])->name('settings.general');
            Route::post('settings/payment',                 [SuperAdminSettingsController::class, 'savePayment'])->name('settings.payment');
            Route::post('settings/smtp',                    [SuperAdminSettingsController::class, 'saveSmtp'])->name('settings.smtp');
            Route::post('settings/localization',            [SuperAdminSettingsController::class, 'saveLocalization'])->name('settings.localization');
            Route::post('settings/maintenance',             [SuperAdminSettingsController::class, 'saveMaintenance'])->name('settings.maintenance');
            Route::post('settings/storage',                 [SuperAdminSettingsController::class, 'saveStorage'])->name('settings.storage');
            Route::post('settings/templates',               [SuperAdminSettingsController::class, 'saveTemplate'])->name('settings.templates');
            Route::post('settings/audit',                   [SuperAdminSettingsController::class, 'saveAudit'])->name('settings.audit');

            Route::get('packages',              [PackageController::class, 'index'])->name('packages.index');
            Route::post('packages',             [PackageController::class, 'store'])->name('packages.store');
            Route::put('packages/{package}',    [PackageController::class, 'update'])->name('packages.update');
            Route::delete('packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

            // Subscriptions
            Route::get('subscriptions',                        [SubscriptionController::class, 'index'])->name('subscriptions.index');
            Route::post('subscriptions',                       [SubscriptionController::class, 'store'])->name('subscriptions.store');
            Route::put('subscriptions/{subscription}',         [SubscriptionController::class, 'update'])->name('subscriptions.update');
            Route::delete('subscriptions/{subscription}',      [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');

            // Coupons
            Route::get('coupons',              [CouponController::class, 'index'])->name('coupons.index');
            Route::post('coupons',             [CouponController::class, 'store'])->name('coupons.store');
            Route::put('coupons/{coupon}',     [CouponController::class, 'update'])->name('coupons.update');
            Route::delete('coupons/{coupon}',  [CouponController::class, 'destroy'])->name('coupons.destroy');

            // Module Manager
            Route::get('module-manager',        [ModuleManagerController::class, 'index'])->name('module-manager.index');
            Route::post('module-manager/toggle', [ModuleManagerController::class, 'toggle'])->name('module-manager.toggle');
            Route::post('module-manager/bulk',   [ModuleManagerController::class, 'bulkSave'])->name('module-manager.bulk');

            // User Management
            Route::get('users',                          [UserManagementController::class, 'index'])->name('users.index');
            Route::post('users',                         [UserManagementController::class, 'store'])->name('users.store');
            Route::put('users/{user}',                   [UserManagementController::class, 'update'])->name('users.update');
            Route::delete('users/{user}',                [UserManagementController::class, 'destroy'])->name('users.destroy');
            Route::patch('users/{user}/suspend',         [UserManagementController::class, 'suspend'])->name('users.suspend');
            Route::patch('users/{user}/activate',        [UserManagementController::class, 'activate'])->name('users.activate');
            Route::patch('users/{user}/reset-password',  [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
        });
});

// Public admission form (no auth)
Route::get('/apply/{school}',  [PublicAdmissionController::class, 'show'])->name('public.admission.show');
Route::post('/apply/{school}', [PublicAdmissionController::class, 'submit'])->name('public.admission.submit');
