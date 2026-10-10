<?php

use App\Http\Controllers\Auth\DemoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\SchoolAdmin\AcademicSetupController;
use App\Http\Controllers\SchoolAdmin\AttendanceController;
use App\Http\Controllers\SchoolAdmin\ExamController;
use App\Http\Controllers\SchoolAdmin\ResultController;
use App\Http\Controllers\SchoolAdmin\ScholarshipController;
use App\Http\Controllers\SchoolAdmin\FeeCategoryController;
use App\Http\Controllers\SchoolAdmin\FeeInvoiceController;
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
use App\Http\Controllers\SchoolAdmin\AdmissionPipelineController;
use App\Http\Controllers\SchoolAdmin\PromotionController;
use App\Http\Controllers\SchoolAdmin\BroadsheetController;
use App\Http\Controllers\SchoolAdmin\CertificateController;
use App\Http\Controllers\SchoolAdmin\CertificateDesignController;
use App\Http\Controllers\SchoolAdmin\CommentBankController;
use App\Http\Controllers\SchoolAdmin\ReportCardController;
use App\Http\Controllers\SchoolAdmin\ReportCardExportController;
use App\Http\Controllers\ReportCardPortalController;
use App\Http\Controllers\SchoolAdmin\ReportCardDesignController;
use App\Http\Controllers\CertificateVerifyController;
use App\Http\Controllers\StudentPhotoController;
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
    Route::get('/', fn () => view('landing'))->name('home');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,10');
    Route::get('/demo', [DemoController::class, 'enter'])->middleware('throttle:20,1')->name('demo');
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

    // Student passport photos: the controller checks the viewer (staff of the school, the student, or their parent)
    Route::get('photos/students/{student}', [StudentPhotoController::class, 'show'])->whereNumber('student')->name('students.photo');

    /*
    |--------------------------------------------------------------------------
    | School Admin routes (school-admin, principal)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super-admin|school-admin|principal|teacher|accountant|librarian')
        ->prefix('school')
        ->name('school.')
        ->group(function () {
            // School setup: anyone on staff can look; changing needs settings.edit
            foreach ([
                'classes'  => ClassController::class,
                'sections' => SectionController::class,
                'subjects' => SubjectController::class,
                'shifts'   => ShiftController::class,
                'holidays' => HolidayController::class,
            ] as $name => $controller) {
                Route::resource($name, $controller)->only(['index']);
                Route::resource($name, $controller)->only(['store', 'update', 'destroy'])->middleware('permission:settings.edit');
            }

            Route::resource('students', StudentController::class)->only(['create', 'store'])->middleware('permission:students.create');
            Route::resource('students', StudentController::class)->only(['index', 'show'])->middleware('permission:students.view');
            Route::resource('students', StudentController::class)->only(['edit', 'update'])->middleware('permission:students.edit');
            // Testimonials and transfer certificates
            Route::get('certificates',                         [CertificateController::class, 'index'])->middleware('permission:students.view')->name('certificates.index');
            Route::get('certificates/designs',                 [CertificateDesignController::class, 'index'])->middleware('permission:students.certificates')->name('certificate-designs.index');
            Route::post('certificates/designs/{type}',         [CertificateDesignController::class, 'update'])->whereIn('type', ['testimonial', 'transfer'])->middleware('permission:settings.edit')->name('certificate-designs.update');
            Route::get('certificates/designs/{type}/preview',  [CertificateDesignController::class, 'preview'])->whereIn('type', ['testimonial', 'transfer'])->middleware('permission:students.certificates')->name('certificate-designs.preview');
            Route::get('certificates/{certificate}/pdf',       [CertificateController::class, 'pdf'])->middleware('permission:students.view')->name('certificates.pdf');
            Route::post('certificates/{certificate}/revoke',   [CertificateController::class, 'revoke'])->middleware('permission:students.certificates')->name('certificates.revoke');
            Route::get('students/{student}/certificates/new',  [CertificateController::class, 'create'])->middleware('permission:students.certificates')->name('certificates.create');
            Route::post('students/{student}/certificates',     [CertificateController::class, 'store'])->middleware('permission:students.certificates')->name('certificates.store');
            Route::resource('students', StudentController::class)->only(['destroy'])->middleware('permission:students.delete');
            Route::post('students/{student}/documents',        [StudentController::class, 'uploadDocument'])->middleware('permission:students.edit')->name('students.documents.upload');
            Route::delete('students/documents/{document}',     [StudentController::class, 'deleteDocument'])->middleware('permission:students.edit')->name('students.documents.delete');
            Route::get('students/documents/{document}/download', [StudentController::class, 'downloadDocument'])->middleware('permission:students.view')->name('students.documents.download');
            Route::post('students/photos',                    [StudentPhotoController::class, 'bulk'])->middleware('permission:students.edit')->name('students.photos.bulk');
            Route::post('students/{student}/photo',            [StudentPhotoController::class, 'store'])->middleware('permission:students.edit')->name('students.photo.store');
            Route::delete('students/{student}/photo',          [StudentPhotoController::class, 'destroy'])->middleware('permission:students.edit')->name('students.photo.destroy');

            // ── Year-end promotion: move a class up, keep some back, graduate the top class ──
            Route::get('promotions',                        [PromotionController::class, 'index'])->middleware('permission:students.promote')->name('promotions.index');
            Route::post('promotions',                       [PromotionController::class, 'store'])->middleware('permission:students.promote')->name('promotions.store');
            Route::post('promotions/{promotionBatch}/undo', [PromotionController::class, 'undo'])->middleware('permission:students.promote')->name('promotions.undo');

            // Exams
            Route::get('exams',                              [ExamController::class, 'index'])->middleware('permission:exams.view')->name('exams.index');
            Route::post('exams',                             [ExamController::class, 'store'])->middleware('permission:exams.create')->name('exams.store');
            Route::put('exams/{exam}',                       [ExamController::class, 'update'])->middleware('permission:exams.edit')->name('exams.update');
            Route::delete('exams/{exam}',                    [ExamController::class, 'destroy'])->middleware('permission:exams.delete')->name('exams.destroy');
            Route::get('exams/{exam}/marks',                 [ExamController::class, 'marks'])->middleware('permission:marks.view')->name('exams.marks');
            Route::post('exams/{exam}/marks',                [ExamController::class, 'saveMarks'])->middleware('permission:marks.entry')->name('exams.marks.save');
            Route::get('exams/{exam}/results',               [ExamController::class, 'results'])->middleware('permission:results.view')->name('exams.results');
            // School years, terms, score setups and grade scales
            Route::get('academics/terms',                      [AcademicSetupController::class, 'terms'])->middleware('permission:exams.view')->name('academics.terms');
            Route::post('academics/years',                     [AcademicSetupController::class, 'storeYear'])->middleware('permission:exams.edit')->name('academics.years.store');
            Route::put('academics/terms/{term}',               [AcademicSetupController::class, 'updateTerm'])->middleware('permission:exams.edit')->name('academics.terms.update');
            Route::post('academics/terms/{term}/current',      [AcademicSetupController::class, 'makeTermCurrent'])->middleware('permission:exams.edit')->name('academics.terms.current');
            Route::get('academics/assessment',                 [AcademicSetupController::class, 'assessment'])->middleware('permission:exams.view')->name('academics.assessment');
            Route::post('academics/assessment-schemes',        [AcademicSetupController::class, 'storeAssessmentScheme'])->middleware('permission:exams.edit')->name('academics.assessment-schemes.store');
            Route::put('academics/assessment-schemes/{scheme}', [AcademicSetupController::class, 'updateAssessmentScheme'])->middleware('permission:exams.edit')->name('academics.assessment-schemes.update');
            Route::delete('academics/assessment-schemes/{scheme}', [AcademicSetupController::class, 'destroyAssessmentScheme'])->middleware('permission:exams.edit')->name('academics.assessment-schemes.destroy');
            Route::post('academics/grading-schemes',           [AcademicSetupController::class, 'storeGradingScheme'])->middleware('permission:exams.edit')->name('academics.grading-schemes.store');
            Route::put('academics/grading-schemes/{scheme}',   [AcademicSetupController::class, 'updateGradingScheme'])->middleware('permission:exams.edit')->name('academics.grading-schemes.update');
            Route::delete('academics/grading-schemes/{scheme}', [AcademicSetupController::class, 'destroyGradingScheme'])->middleware('permission:exams.edit')->name('academics.grading-schemes.destroy');
            Route::put('academics/classes/{class}/scheme',     [AcademicSetupController::class, 'assignClass'])->middleware('permission:exams.edit')->name('academics.classes.scheme');
            Route::put('academics/subjects/{subject}/scheme',  [AcademicSetupController::class, 'assignSubject'])->middleware('permission:exams.edit')->name('academics.subjects.scheme');
            Route::post('academics/behaviour-traits',          [AcademicSetupController::class, 'storeTrait'])->middleware('permission:exams.edit')->name('academics.traits.store');
            Route::put('academics/behaviour-traits/{trait}',   [AcademicSetupController::class, 'updateTrait'])->middleware('permission:exams.edit')->name('academics.traits.update');
            Route::delete('academics/behaviour-traits/{trait}', [AcademicSetupController::class, 'destroyTrait'])->middleware('permission:exams.edit')->name('academics.traits.destroy');

            // Term results: part scores, behaviour ratings, positions and the approval steps
            Route::get('results',                              [ResultController::class, 'index'])->middleware('permission:results.view')->name('results.index');
            Route::get('results/comment-bank',                   [CommentBankController::class, 'index'])->middleware('permission:results.view')->name('comment-bank.index');
            Route::get('results/print',                          [ReportCardExportController::class, 'index'])->middleware('permission:reportcard.generate')->name('report-card-exports.index');
            Route::post('results/print',                         [ReportCardExportController::class, 'store'])->middleware('permission:reportcard.generate')->name('report-card-exports.store');
            Route::post('results/print/{export}/step',           [ReportCardExportController::class, 'step'])->middleware('permission:reportcard.generate')->name('report-card-exports.step');
            Route::get('results/print/{export}/download',        [ReportCardExportController::class, 'download'])->middleware('permission:reportcard.generate')->name('report-card-exports.download');
            Route::get('results/broadsheets',                    [BroadsheetController::class, 'school'])->middleware('permission:results.view')->name('broadsheets.school');
            Route::get('results/{sheet}',                      [ResultController::class, 'show'])->middleware('permission:results.view')->name('results.show');
            Route::post('results/{sheet}/scores',              [ResultController::class, 'saveScores'])->middleware('permission:marks.entry')->name('results.scores');
            Route::post('results/{sheet}/ratings',             [ResultController::class, 'saveRatings'])->middleware('permission:marks.entry')->name('results.ratings');
            // Each step checks its own permission (see ResultSheet::ACTIONS)
            Route::post('results/{sheet}/status',              [ResultController::class, 'transition'])->middleware('permission:results.view')->name('results.status');
            // Report card designs: layout, colours, what shows, and who comments and signs
            Route::get('academics/report-card-designs',                   [ReportCardDesignController::class, 'index'])->middleware('permission:reportcard.generate')->name('report-card-designs.index');
            Route::post('academics/report-card-designs',                  [ReportCardDesignController::class, 'store'])->middleware('permission:settings.edit')->name('report-card-designs.store');
            Route::post('academics/report-card-designs/{design}',         [ReportCardDesignController::class, 'update'])->middleware('permission:settings.edit')->name('report-card-designs.update');
            Route::delete('academics/report-card-designs/{design}',       [ReportCardDesignController::class, 'destroy'])->middleware('permission:settings.edit')->name('report-card-designs.destroy');
            Route::post('academics/report-card-designs/{design}/classes', [ReportCardDesignController::class, 'assignClasses'])->middleware('permission:settings.edit')->name('report-card-designs.classes');
            Route::get('academics/report-card-designs/{design}/preview',  [ReportCardDesignController::class, 'preview'])->middleware('permission:reportcard.generate')->name('report-card-designs.preview');

            // Report cards: comments, then the term and full-year PDFs (one student or the whole class)
            // Broadsheets: every student and subject of a class on one page (PDF or Excel)
            Route::get('results/{sheet}/broadsheet',             [BroadsheetController::class, 'term'])->middleware('permission:results.view')->name('results.broadsheet');
            Route::get('results/{sheet}/broadsheet/session',     [BroadsheetController::class, 'session'])->middleware('permission:results.view')->name('results.broadsheet.session');
            Route::get('results/{sheet}/report-cards',           [ReportCardController::class, 'index'])->middleware('permission:reportcard.generate')->name('results.report-cards');
            Route::get('results/{sheet}/report-cards/term',      [ReportCardController::class, 'term'])->middleware('permission:reportcard.generate')->name('results.report-cards.term');
            Route::get('results/{sheet}/report-cards/session',   [ReportCardController::class, 'session'])->middleware('permission:reportcard.generate')->name('results.report-cards.session');
            // Who may write each comment is set on the signer (teachers or heads); the controller checks it
            Route::post('results/{sheet}/comments',              [ReportCardController::class, 'saveComments'])->middleware('permission:results.view')->name('results.comments');
            Route::post('results/{sheet}/comments/fill',         [ReportCardController::class, 'fillComments'])->middleware('permission:results.view')->name('results.comments.fill');
            Route::post('results/comment-bank',                  [CommentBankController::class, 'store'])->middleware('permission:results.view')->name('comment-bank.store');
            Route::post('results/comment-bank/starters',         [CommentBankController::class, 'starters'])->middleware('permission:results.view')->name('comment-bank.starters');
            Route::post('results/comment-bank/apply',            [CommentBankController::class, 'apply'])->middleware('permission:results.view')->name('comment-bank.apply');
            Route::put('results/comment-bank/{entry}',           [CommentBankController::class, 'update'])->middleware('permission:results.view')->name('comment-bank.update');
            Route::delete('results/comment-bank/{entry}',        [CommentBankController::class, 'destroy'])->middleware('permission:results.view')->name('comment-bank.destroy');
            Route::redirect('grade-scales', '/school/academics/assessment')->middleware('permission:exams.view')->name('grade-scales.index');

            // Timetable
            Route::get('timetable',                      [TimetableController::class, 'index'])->middleware('permission:timetable.view')->name('timetable.index');
            Route::post('timetable',                     [TimetableController::class, 'store'])->middleware('permission:timetable.manage')->name('timetable.store');
            Route::delete('timetable/{timetable}',       [TimetableController::class, 'destroy'])->middleware('permission:timetable.manage')->name('timetable.destroy');
            Route::get('timetable/teacher',              [TimetableController::class, 'teacherSchedule'])->middleware('permission:timetable.view')->name('timetable.teacher');

            // Attendance — student
            Route::get('attendance',                          [AttendanceController::class, 'index'])->middleware('permission:attendance.view')->name('attendance.index');
            Route::post('attendance',                         [AttendanceController::class, 'store'])->middleware('permission:attendance.mark')->name('attendance.store');
            Route::get('attendance/students/{student}/calendar', [AttendanceController::class, 'studentCalendar'])->middleware('permission:attendance.view')->name('attendance.student.calendar');
            // Attendance — staff
            Route::get('attendance/staff',                    [AttendanceController::class, 'staffIndex'])->middleware('permission:attendance.view')->name('attendance.staff.index');
            Route::post('attendance/staff',                   [AttendanceController::class, 'staffStore'])->middleware('permission:attendance.mark')->name('attendance.staff.store');

            // HR — Leave Management
            Route::get('hr/leave-types',                       [LeaveController::class, 'types'])->middleware('permission:leave.view')->name('hr.leave-types.index');
            Route::post('hr/leave-types',                      [LeaveController::class, 'storeType'])->middleware('permission:leave.approve')->name('hr.leave-types.store');
            Route::put('hr/leave-types/{leaveType}',           [LeaveController::class, 'updateType'])->middleware('permission:leave.approve')->name('hr.leave-types.update');
            Route::delete('hr/leave-types/{leaveType}',        [LeaveController::class, 'destroyType'])->middleware('permission:leave.approve')->name('hr.leave-types.destroy');
            Route::get('hr/leaves',                            [LeaveController::class, 'index'])->middleware('permission:leave.view')->name('hr.leaves.index');
            Route::post('hr/leaves',                           [LeaveController::class, 'store'])->middleware('permission:leave.apply')->name('hr.leaves.store');
            Route::put('hr/leaves/{leaveRequest}/approve',     [LeaveController::class, 'approve'])->middleware('permission:leave.approve')->name('hr.leaves.approve');

            // HR — Payroll
            Route::get('hr/salary-structure',                  [PayrollController::class, 'structure'])->middleware('permission:payroll.view')->name('hr.salary-structure.index');
            Route::put('hr/salary-structure/{staff}',          [PayrollController::class, 'saveStructure'])->middleware('permission:payroll.generate')->name('hr.salary-structure.save');
            Route::get('hr/payroll',                           [PayrollController::class, 'index'])->middleware('permission:payroll.view')->name('hr.payroll.index');
            Route::post('hr/payroll/generate',                 [PayrollController::class, 'generate'])->middleware('permission:payroll.generate')->name('hr.payroll.generate');
            Route::put('hr/payroll/{payroll}/paid',            [PayrollController::class, 'markPaid'])->middleware('permission:payroll.generate')->name('hr.payroll.paid');
            Route::get('hr/payroll/{payroll}/slip',            [PayrollController::class, 'slip'])->middleware('permission:payroll.view|payslip.download')->name('hr.payroll.slip');

            // Library Management
            Route::get('library/books',                        [LibraryController::class, 'index'])->middleware('permission:library.view')->name('library.books.index');
            Route::post('library/books',                       [LibraryController::class, 'store'])->middleware('permission:library.manage')->name('library.books.store');
            Route::put('library/books/{book}',                 [LibraryController::class, 'update'])->middleware('permission:library.manage')->name('library.books.update');
            Route::delete('library/books/{book}',              [LibraryController::class, 'destroy'])->middleware('permission:library.manage')->name('library.books.destroy');
            Route::get('library/issues',                       [LibraryController::class, 'issues'])->middleware('permission:library.view')->name('library.issues.index');
            Route::post('library/issues',                      [LibraryController::class, 'issueBook'])->middleware('permission:library.issue')->name('library.issues.store');
            Route::put('library/issues/{bookIssue}/return',    [LibraryController::class, 'returnBook'])->middleware('permission:library.issue')->name('library.issues.return');
            Route::get('library/overdue',                      [LibraryController::class, 'overdue'])->middleware('permission:library.view')->name('library.overdue');

            // Inventory Management
            Route::get('inventory/categories',                         [InventoryController::class, 'categories'])->middleware('permission:inventory.view')->name('inventory.categories');
            Route::post('inventory/categories',                        [InventoryController::class, 'storeCategory'])->middleware('permission:inventory.manage')->name('inventory.categories.store');
            Route::put('inventory/categories/{inventoryCategory}',     [InventoryController::class, 'updateCategory'])->middleware('permission:inventory.manage')->name('inventory.categories.update');
            Route::delete('inventory/categories/{inventoryCategory}',  [InventoryController::class, 'destroyCategory'])->middleware('permission:inventory.manage')->name('inventory.categories.destroy');

            Route::get('inventory/items',                              [InventoryController::class, 'items'])->middleware('permission:inventory.view')->name('inventory.items');
            Route::post('inventory/items',                             [InventoryController::class, 'storeItem'])->middleware('permission:inventory.manage')->name('inventory.items.store');
            Route::put('inventory/items/{inventoryItem}',              [InventoryController::class, 'updateItem'])->middleware('permission:inventory.manage')->name('inventory.items.update');
            Route::delete('inventory/items/{inventoryItem}',           [InventoryController::class, 'destroyItem'])->middleware('permission:inventory.manage')->name('inventory.items.destroy');

            Route::get('inventory/purchases',                          [InventoryController::class, 'purchases'])->middleware('permission:inventory.view')->name('inventory.purchases');
            Route::post('inventory/purchases',                         [InventoryController::class, 'storePurchase'])->middleware('permission:inventory.manage')->name('inventory.purchases.store');

            Route::get('inventory/issues',                             [InventoryController::class, 'issues'])->middleware('permission:inventory.view')->name('inventory.issues');
            Route::post('inventory/issues',                            [InventoryController::class, 'storeIssue'])->middleware('permission:inventory.issue')->name('inventory.issues.store');
            Route::put('inventory/issues/{inventoryIssue}/return',     [InventoryController::class, 'returnIssue'])->middleware('permission:inventory.issue')->name('inventory.issues.return');

            // Asset Management
            Route::get('inventory/assets',                             [AssetController::class, 'index'])->middleware('permission:inventory.view')->name('inventory.assets');
            Route::post('inventory/assets',                            [AssetController::class, 'store'])->middleware('permission:inventory.manage')->name('inventory.assets.store');
            Route::get('inventory/assets/{asset}',                     [AssetController::class, 'show'])->middleware('permission:inventory.view')->name('inventory.assets.show');
            Route::put('inventory/assets/{asset}',                     [AssetController::class, 'update'])->middleware('permission:inventory.manage')->name('inventory.assets.update');
            Route::delete('inventory/assets/{asset}',                  [AssetController::class, 'destroy'])->middleware('permission:inventory.manage')->name('inventory.assets.destroy');
            Route::post('inventory/assets/{asset}/maintenance',        [AssetController::class, 'storeMaintenance'])->middleware('permission:inventory.manage')->name('inventory.assets.maintenance');

            // Hostel Management
            Route::get('hostel',                                          [HostelController::class, 'index'])->middleware('permission:hostel.view')->name('hostel.index');
            Route::post('hostel',                                         [HostelController::class, 'store'])->middleware('permission:hostel.manage')->name('hostel.store');
            Route::put('hostel/{hostel}',                                 [HostelController::class, 'update'])->middleware('permission:hostel.manage')->name('hostel.update');
            Route::delete('hostel/{hostel}',                              [HostelController::class, 'destroy'])->middleware('permission:hostel.manage')->name('hostel.destroy');

            Route::get('hostel/{hostel}/rooms',                           [HostelController::class, 'rooms'])->middleware('permission:hostel.view')->name('hostel.rooms');
            Route::post('hostel/{hostel}/rooms',                          [HostelController::class, 'storeRoom'])->middleware('permission:hostel.manage')->name('hostel.rooms.store');
            Route::put('hostel/{hostel}/rooms/{room}',                    [HostelController::class, 'updateRoom'])->middleware('permission:hostel.manage')->name('hostel.rooms.update');
            Route::delete('hostel/{hostel}/rooms/{room}',                 [HostelController::class, 'destroyRoom'])->middleware('permission:hostel.manage')->name('hostel.rooms.destroy');
            Route::get('hostel/{hostel}/available-rooms',                 [HostelController::class, 'hostelRooms'])->middleware('permission:hostel.view')->name('hostel.available-rooms');

            Route::get('hostel/allocations',                              [HostelController::class, 'allocations'])->middleware('permission:hostel.view')->name('hostel.allocations');
            Route::post('hostel/allocations',                             [HostelController::class, 'storeAllocation'])->middleware('permission:hostel.manage')->name('hostel.allocations.store');
            Route::put('hostel/allocations/{allocation}/vacate',          [HostelController::class, 'vacate'])->middleware('permission:hostel.manage')->name('hostel.vacate');

            // Transport Management
            Route::get('transport/vehicles',                          [TransportController::class, 'vehicles'])->middleware('permission:transport.view')->name('transport.vehicles');
            Route::post('transport/vehicles',                         [TransportController::class, 'storeVehicle'])->middleware('permission:transport.manage')->name('transport.vehicles.store');
            Route::put('transport/vehicles/{vehicle}',                [TransportController::class, 'updateVehicle'])->middleware('permission:transport.manage')->name('transport.vehicles.update');
            Route::delete('transport/vehicles/{vehicle}',             [TransportController::class, 'destroyVehicle'])->middleware('permission:transport.manage')->name('transport.vehicles.destroy');

            Route::get('transport/routes',                            [TransportController::class, 'routes'])->middleware('permission:transport.view')->name('transport.routes');
            Route::post('transport/routes',                           [TransportController::class, 'storeRoute'])->middleware('permission:transport.manage')->name('transport.routes.store');
            Route::put('transport/routes/{route}',                    [TransportController::class, 'updateRoute'])->middleware('permission:transport.manage')->name('transport.routes.update');
            Route::delete('transport/routes/{route}',                 [TransportController::class, 'destroyRoute'])->middleware('permission:transport.manage')->name('transport.routes.destroy');

            Route::get('transport/routes/{route}/assignments',        [TransportController::class, 'assignments'])->middleware('permission:transport.view')->name('transport.assignments');
            Route::post('transport/routes/{route}/assign',            [TransportController::class, 'assignStudent'])->middleware('permission:transport.manage')->name('transport.assign');
            Route::delete('transport/routes/{route}/students/{student}', [TransportController::class, 'removeStudent'])->middleware('permission:transport.manage')->name('transport.unassign');

            // Homework & Lesson Planning
            Route::get('homework',                                    [HomeworkController::class, 'index'])->middleware('permission:homework.view')->name('homework.index');
            Route::post('homework',                                   [HomeworkController::class, 'store'])->middleware('permission:homework.create')->name('homework.store');
            Route::put('homework/{homework}',                         [HomeworkController::class, 'update'])->middleware('permission:homework.create')->name('homework.update');
            Route::delete('homework/{homework}',                      [HomeworkController::class, 'destroy'])->middleware('permission:homework.create')->name('homework.destroy');
            Route::get('homework/{homework}/submissions',             [HomeworkController::class, 'submissions'])->middleware('permission:homework.view')->name('homework.submissions');
            Route::put('homework/submissions/{submission}/review',    [HomeworkController::class, 'reviewSubmission'])->middleware('permission:homework.grade')->name('homework.submissions.review');

            Route::get('homework/lesson-plans',                       [HomeworkController::class, 'lessonPlans'])->middleware('permission:lessons.view')->name('homework.lesson-plans.index');
            Route::post('homework/lesson-plans',                      [HomeworkController::class, 'storeLessonPlan'])->middleware('permission:lessons.create')->name('homework.lesson-plans.store');
            Route::put('homework/lesson-plans/{lessonPlan}/review',   [HomeworkController::class, 'reviewLessonPlan'])->middleware('permission:lessons.approve')->name('homework.lesson-plans.review');
            Route::delete('homework/lesson-plans/{lessonPlan}',       [HomeworkController::class, 'destroyLessonPlan'])->middleware('permission:lessons.create')->name('homework.lesson-plans.destroy');

            Route::get('homework/syllabi',                            [HomeworkController::class, 'syllabi'])->middleware('permission:syllabus.view')->name('homework.syllabi.index');
            Route::post('homework/syllabi',                           [HomeworkController::class, 'storeSyllabus'])->middleware('permission:syllabus.manage')->name('homework.syllabi.store');
            Route::put('homework/syllabi/{syllabus}',                 [HomeworkController::class, 'updateSyllabus'])->middleware('permission:syllabus.manage')->name('homework.syllabi.update');

            Route::get('homework/online-classes',                     [HomeworkController::class, 'onlineClasses'])->middleware('permission:homework.view')->name('homework.online-classes.index');
            Route::post('homework/online-classes',                    [HomeworkController::class, 'storeOnlineClass'])->middleware('permission:homework.create')->name('homework.online-classes.store');
            Route::put('homework/online-classes/{onlineClass}/status',[HomeworkController::class, 'updateOnlineClassStatus'])->middleware('permission:homework.create')->name('homework.online-classes.status');
            Route::delete('homework/online-classes/{onlineClass}',    [HomeworkController::class, 'destroyOnlineClass'])->middleware('permission:homework.create')->name('homework.online-classes.destroy');

            // Fee Management
            Route::get('fees/categories',                    [FeeCategoryController::class, 'index'])->middleware('permission:fees.view')->name('fees.categories.index');
            Route::post('fees/categories',                   [FeeCategoryController::class, 'store'])->middleware('permission:fees.structure')->name('fees.categories.store');
            Route::put('fees/categories/{feeCategory}',      [FeeCategoryController::class, 'update'])->middleware('permission:fees.structure')->name('fees.categories.update');
            Route::delete('fees/categories/{feeCategory}',   [FeeCategoryController::class, 'destroy'])->middleware('permission:fees.structure')->name('fees.categories.destroy');

            Route::get('fees/structures',                    [FeeStructureController::class, 'index'])->middleware('permission:fees.view')->name('fees.structures.index');
            Route::post('fees/structures',                   [FeeStructureController::class, 'store'])->middleware('permission:fees.structure')->name('fees.structures.store');
            Route::put('fees/structures/{feeStructure}',     [FeeStructureController::class, 'update'])->middleware('permission:fees.structure')->name('fees.structures.update');
            Route::delete('fees/structures/{feeStructure}',  [FeeStructureController::class, 'destroy'])->middleware('permission:fees.structure')->name('fees.structures.destroy');

            Route::get('fees/payments',                      [FeePaymentController::class, 'index'])->middleware('permission:fees.view')->name('fees.payments.index');
            Route::get('fees/payments/collect',              [FeePaymentController::class, 'create'])->middleware('permission:fees.collect')->name('fees.payments.create');
            Route::post('fees/payments',                     [FeePaymentController::class, 'store'])->middleware('permission:fees.collect')->name('fees.payments.store');
            Route::get('fees/payments/{feePayment}',         [FeePaymentController::class, 'show'])->middleware('permission:fees.view')->name('fees.payments.show');
            // Invoices and the fee ledger (lines are never edited; mistakes are reversed)
            Route::get('fees/invoices',                      [FeeInvoiceController::class, 'index'])->middleware('permission:fees.view')->name('fees.invoices.index');
            Route::post('fees/invoices',                     [FeeInvoiceController::class, 'issue'])->middleware('permission:fees.structure')->name('fees.invoices.issue');
            Route::get('fees/invoices/{invoice}',            [FeeInvoiceController::class, 'show'])->middleware('permission:fees.view')->name('fees.invoices.show');
            Route::post('fees/invoices/{invoice}/payments',  [FeeInvoiceController::class, 'pay'])->middleware('permission:fees.collect')->name('fees.invoices.pay');
            Route::post('fees/invoices/{invoice}/fines',     [FeeInvoiceController::class, 'fine'])->middleware('permission:fees.collect')->name('fees.invoices.fine');
            Route::post('fees/invoices/{invoice}/entries/{entry}/reverse', [FeeInvoiceController::class, 'reverse'])->middleware('permission:fees.waiver')->name('fees.invoices.reverse');
            Route::post('fees/invoices/{invoice}/void',      [FeeInvoiceController::class, 'void'])->middleware('permission:fees.waiver')->name('fees.invoices.void');

            // Named scholarships and discounts, with who approved each one
            Route::get('fees/scholarships',                  [ScholarshipController::class, 'index'])->middleware('permission:fees.view')->name('fees.scholarships.index');
            Route::post('fees/scholarships',                 [ScholarshipController::class, 'store'])->middleware('permission:fees.waiver')->name('fees.scholarships.store');
            Route::put('fees/scholarships/{scholarship}',    [ScholarshipController::class, 'update'])->middleware('permission:fees.waiver')->name('fees.scholarships.update');
            Route::delete('fees/scholarships/{scholarship}', [ScholarshipController::class, 'destroy'])->middleware('permission:fees.waiver')->name('fees.scholarships.destroy');
            Route::post('fees/scholarships/awards',          [ScholarshipController::class, 'award'])->middleware('permission:fees.waiver')->name('fees.scholarships.award');
            Route::post('fees/scholarships/awards/{award}/revoke', [ScholarshipController::class, 'revoke'])->middleware('permission:fees.waiver')->name('fees.scholarships.revoke');

            Route::get('fees/outstanding',                   [FeePaymentController::class, 'outstanding'])->middleware('permission:fees.reports')->name('fees.outstanding');

            // Communication
            Route::get('communication/announcements',                              [CommunicationController::class, 'announcements'])->middleware('permission:announcements.view')->name('communication.announcements');
            Route::post('communication/announcements',                             [CommunicationController::class, 'storeAnnouncement'])->middleware('permission:announcements.create')->name('communication.announcements.store');
            Route::put('communication/announcements/{announcement}',               [CommunicationController::class, 'updateAnnouncement'])->middleware('permission:announcements.create')->name('communication.announcements.update');
            Route::delete('communication/announcements/{announcement}',            [CommunicationController::class, 'destroyAnnouncement'])->middleware('permission:announcements.delete')->name('communication.announcements.destroy');

            Route::get('communication/messages',                                   [CommunicationController::class, 'messages'])->middleware('permission:messages.view')->name('communication.messages');
            Route::post('communication/messages',                                  [CommunicationController::class, 'sendMessage'])->middleware('permission:messages.send')->name('communication.messages.send');
            Route::put('communication/messages/{message}/read',                    [CommunicationController::class, 'readMessage'])->middleware('permission:messages.view')->name('communication.messages.read');

            Route::get('communication/blast',                                      [CommunicationController::class, 'blast'])->middleware('permission:sms.send|email.send')->name('communication.blast');
            Route::post('communication/blast',                                     [CommunicationController::class, 'sendBlast'])->middleware('permission:sms.send|email.send')->name('communication.blast.send');

            Route::get('communication/email-templates',                            [CommunicationController::class, 'emailTemplates'])->middleware('permission:email.send')->name('communication.email-templates');
            Route::post('communication/email-templates',                           [CommunicationController::class, 'storeEmailTemplate'])->middleware('permission:email.send')->name('communication.email-templates.store');
            Route::put('communication/email-templates/{emailTemplate}',            [CommunicationController::class, 'updateEmailTemplate'])->middleware('permission:email.send')->name('communication.email-templates.update');

            Route::get('communication/notifications',                              [CommunicationController::class, 'notifications'])->name('communication.notifications');
            Route::put('communication/notifications/{notification}/read',          [CommunicationController::class, 'markNotificationRead'])->name('communication.notifications.read');
            Route::put('communication/notifications/read-all',                     [CommunicationController::class, 'markAllNotificationsRead'])->name('communication.notifications.read-all');

            // Reports & Analytics
            Route::get('reports/dashboard',                     [ReportController::class, 'dashboard'])->middleware('permission:reports.view')->name('reports.dashboard');
            Route::get('reports/attendance',                    [ReportController::class, 'attendance'])->middleware('permission:reports.view')->name('reports.attendance');
            Route::get('reports/academic',                      [ReportController::class, 'academic'])->middleware('permission:reports.view')->name('reports.academic');
            Route::get('reports/finance',                       [ReportController::class, 'finance'])->middleware('permission:reports.view')->name('reports.finance');
            Route::get('reports/custom',                        [ReportController::class, 'customBuilder'])->middleware('permission:reports.custom')->name('reports.custom');
            Route::post('reports/custom/run',                   [ReportController::class, 'runCustomReport'])->middleware('permission:reports.custom')->name('reports.custom.run');
            Route::get('reports/custom/export-csv',             [ReportController::class, 'exportCsv'])->middleware('permission:reports.custom')->name('reports.custom.csv');
            Route::get('reports/attendance/export-pdf',         [ReportController::class, 'exportAttendancePdf'])->middleware('permission:reports.export')->name('reports.attendance.pdf');
            Route::get('reports/finance/export-pdf',            [ReportController::class, 'exportFinancePdf'])->middleware('permission:reports.export')->name('reports.finance.pdf');
            Route::get('reports/audit-log',                     [ReportController::class, 'auditLog'])->middleware('permission:settings.view')->name('reports.audit-log');

            // General / Branding / Academic / Notification Settings
            Route::get('settings',                              [SchoolSettingsController::class, 'index'])->middleware('permission:settings.view')->name('settings.index');
            Route::post('settings/general',                     [SchoolSettingsController::class, 'saveGeneral'])->middleware('permission:settings.edit')->name('settings.general');
            Route::post('settings/branding',                    [SchoolSettingsController::class, 'saveBranding'])->middleware('permission:settings.edit')->name('settings.branding');
            Route::post('settings/academic',                    [SchoolSettingsController::class, 'saveAcademic'])->middleware('permission:settings.edit')->name('settings.academic');
            Route::post('settings/notifications',               [SchoolSettingsController::class, 'saveNotifications'])->middleware('permission:settings.edit')->name('settings.notifications');

            // Integrations / Gateway Settings
            Route::get('settings/integrations',                 [IntegrationController::class, 'index'])->middleware('permission:settings.edit')->name('settings.integrations');
            Route::post('settings/integrations/smtp',           [IntegrationController::class, 'saveSmtp'])->middleware('permission:settings.edit')->name('settings.integrations.smtp');
            Route::post('settings/integrations/smtp/test',      [IntegrationController::class, 'testSmtp'])->middleware('permission:settings.edit')->name('settings.integrations.smtp.test');
            Route::post('settings/integrations/sms',            [IntegrationController::class, 'saveSms'])->middleware('permission:settings.edit')->name('settings.integrations.sms');
            Route::post('settings/integrations/sms/test',       [IntegrationController::class, 'testSms'])->middleware('permission:settings.edit')->name('settings.integrations.sms.test');

            // School User / Admin Management
            Route::get('settings/admins',                       [SchoolUserController::class, 'index'])->middleware('permission:users.view')->name('settings.admins');
            Route::post('settings/admins',                      [SchoolUserController::class, 'store'])->middleware('permission:users.create')->name('settings.admins.store');
            Route::put('settings/admins/{user}',                [SchoolUserController::class, 'update'])->middleware('permission:users.edit')->name('settings.admins.update');
            Route::delete('settings/admins/{user}',             [SchoolUserController::class, 'destroy'])->middleware('permission:users.delete')->name('settings.admins.destroy');
            Route::patch('settings/admins/{user}/suspend',      [SchoolUserController::class, 'suspend'])->middleware('permission:users.edit')->name('settings.admins.suspend');
            Route::patch('settings/admins/{user}/activate',     [SchoolUserController::class, 'activate'])->middleware('permission:users.edit')->name('settings.admins.activate');

            // Admission Inquiries
            Route::get('admissions/inquiries',                          [AdmissionInquiryController::class, 'index'])->middleware('permission:students.view')->name('admissions.inquiries');
            Route::post('admissions/inquiries',                         [AdmissionInquiryController::class, 'store'])->middleware('permission:students.create')->name('admissions.inquiries.store');
            Route::put('admissions/inquiries/{admissionInquiry}',       [AdmissionInquiryController::class, 'update'])->middleware('permission:students.create')->name('admissions.inquiries.update');
            Route::delete('admissions/inquiries/{admissionInquiry}',    [AdmissionInquiryController::class, 'destroy'])->middleware('permission:students.edit')->name('admissions.inquiries.destroy');
            Route::post('admissions/inquiries/{admissionInquiry}/followup', [AdmissionInquiryController::class, 'addFollowup'])->middleware('permission:students.create')->name('admissions.inquiries.followup');
            Route::get('admissions/inquiries/{admissionInquiry}',       [AdmissionPipelineController::class, 'show'])->middleware('permission:students.view')->name('admissions.inquiries.show');
            Route::post('admissions/inquiries/{admissionInquiry}/assessments', [AdmissionPipelineController::class, 'storeAssessment'])->middleware('permission:admissions.manage')->name('admissions.assessments.store');
            Route::put('admissions/inquiries/{admissionInquiry}/assessments/{assessment}', [AdmissionPipelineController::class, 'updateAssessment'])->middleware('permission:admissions.manage')->name('admissions.assessments.update');
            Route::delete('admissions/inquiries/{admissionInquiry}/assessments/{assessment}', [AdmissionPipelineController::class, 'destroyAssessment'])->middleware('permission:admissions.manage')->name('admissions.assessments.destroy');
            Route::post('admissions/inquiries/{admissionInquiry}/decision', [AdmissionPipelineController::class, 'decide'])->middleware('permission:admissions.manage')->name('admissions.inquiries.decide');
            Route::post('admissions/inquiries/{admissionInquiry}/enrol', [AdmissionPipelineController::class, 'enrol'])->middleware('permission:students.create')->name('admissions.inquiries.enrol');

            // Visitor Logs
            Route::get('admissions/visitors',                [VisitorLogController::class, 'index'])->middleware('permission:students.view')->name('admissions.visitors');
            Route::post('admissions/visitors',               [VisitorLogController::class, 'store'])->middleware('permission:students.create')->name('admissions.visitors.store');
            Route::patch('admissions/visitors/{visitorLog}/checkout', [VisitorLogController::class, 'checkout'])->middleware('permission:students.create')->name('admissions.visitors.checkout');
            Route::delete('admissions/visitors/{visitorLog}', [VisitorLogController::class, 'destroy'])->middleware('permission:students.edit')->name('admissions.visitors.destroy');

            Route::resource('departments',  DepartmentController::class)->only(['index'])->middleware('permission:staff.view');
            Route::resource('departments',  DepartmentController::class)->only(['store', 'update', 'destroy'])->middleware('permission:staff.edit');
            Route::resource('designations', DesignationController::class)->only(['index'])->middleware('permission:staff.view');
            Route::resource('designations', DesignationController::class)->only(['store', 'update', 'destroy'])->middleware('permission:staff.edit');
            Route::resource('staff', StaffController::class)->only(['create', 'store'])->middleware('permission:staff.create');
            Route::resource('staff', StaffController::class)->only(['index', 'show'])->middleware('permission:staff.view');
            Route::resource('staff', StaffController::class)->only(['edit', 'update'])->middleware('permission:staff.edit');
            Route::resource('staff', StaffController::class)->only(['destroy'])->middleware('permission:staff.delete');
            Route::post('staff/{staff}/documents',         [StaffController::class, 'uploadDocument'])->middleware('permission:staff.edit')->name('staff.documents.upload');
            Route::delete('staff/documents/{document}',    [StaffController::class, 'deleteDocument'])->middleware('permission:staff.edit')->name('staff.documents.delete');
            Route::get('staff/documents/{document}/download', [StaffController::class, 'downloadDocument'])->middleware('permission:staff.view')->name('staff.documents.download');
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
        Route::get('report-cards/{sheet}',         [ReportCardPortalController::class, 'studentTerm'])->whereNumber('sheet')->name('report-cards.term');
        Route::get('report-cards/{sheet}/session', [ReportCardPortalController::class, 'studentSession'])->whereNumber('sheet')->name('report-cards.session');
        Route::get('homework',      [StudentPortalController::class, 'homework'])->name('homework');
        Route::get('fees',          [StudentPortalController::class, 'fees'])->name('fees');
        Route::get('announcements', [StudentPortalController::class, 'announcements'])->name('announcements');
    });

    Route::middleware('role:parent')->prefix('school/parent')->name('parent.')->group(function () {
        Route::get('dashboard',     [ParentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('attendance',    [ParentPortalController::class, 'attendance'])->name('attendance');
        Route::get('results',       [ParentPortalController::class, 'results'])->name('results');
        Route::get('report-cards/{student}/{sheet}',         [ReportCardPortalController::class, 'parentTerm'])->whereNumber(['student', 'sheet'])->name('report-cards.term');
        Route::get('report-cards/{student}/{sheet}/session', [ReportCardPortalController::class, 'parentSession'])->whereNumber(['student', 'sheet'])->name('report-cards.session');
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

// Vehicle GPS devices post their position here. No login: each vehicle has its own secret token.
Route::post('/webhooks/vehicles/{vehicle}/location', [TransportController::class, 'trackingWebhook'])
    ->middleware('throttle:120,1')
    ->name('webhooks.vehicle-location');

// Public admission form (no auth)
// Anyone can check that a testimonial or transfer certificate is genuine, using the code printed on it
Route::get('/verify/{code}', [CertificateVerifyController::class, 'show'])->where('code', '[A-Za-z0-9]{6,16}')->middleware('throttle:30,1')->name('certificates.verify');
Route::get('/apply/{school}',  [PublicAdmissionController::class, 'show'])->name('public.admission.show');
Route::post('/apply/{school}', [PublicAdmissionController::class, 'submit'])->name('public.admission.submit');
