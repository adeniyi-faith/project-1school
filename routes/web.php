<?php

use App\Http\Controllers\Auth\DemoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\SchoolAdmin\AcademicSetupController;
use App\Http\Controllers\SchoolAdmin\AttendanceController;
use App\Http\Controllers\SchoolAdmin\ExamController;
use App\Http\Controllers\SchoolAdmin\LessonNoteController;
use App\Http\Controllers\SchoolAdmin\ResultController;
use App\Http\Controllers\SchoolAdmin\ScholarshipController;
use App\Http\Controllers\SchoolAdmin\FeeCategoryController;
use App\Http\Controllers\SchoolAdmin\FeeInvoiceController;
use App\Http\Controllers\SchoolAdmin\FeePaymentController;
use App\Http\Controllers\SchoolAdmin\FeeStructureController;
use App\Http\Controllers\SchoolAdmin\CommunicationController;
use App\Http\Controllers\SchoolAdmin\MessageLogController;
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
use App\Http\Controllers\SchoolAdmin\MyClassesController;
use App\Http\Controllers\SchoolAdmin\StaffController;
use App\Http\Controllers\SchoolAdmin\StudentController;
use App\Http\Controllers\SchoolAdmin\StudentImportController;
use App\Http\Controllers\SchoolAdmin\StudentLoginController;
use App\Http\Controllers\SchoolAdmin\StudentRenumberController;
use App\Http\Controllers\SchoolAdmin\SubjectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolAdmin\SchoolUserController;
use App\Http\Controllers\SchoolAdmin\SettingsController as SchoolSettingsController;
use App\Http\Controllers\SuperAdmin\SettingsController as SuperAdminSettingsController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SchoolAdmin\AdmissionInquiryController;
use App\Http\Controllers\SchoolAdmin\AdmissionPipelineController;
use App\Http\Controllers\SchoolAdmin\NewTermController;
use App\Http\Controllers\SchoolAdmin\PromotionController;
use App\Http\Controllers\SchoolAdmin\BroadsheetController;
use App\Http\Controllers\SchoolAdmin\CertificateController;
use App\Http\Controllers\SchoolAdmin\CertificateDesignController;
use App\Http\Controllers\SchoolAdmin\CbtBankController;
use App\Http\Controllers\SchoolAdmin\CbtTestController;
use App\Http\Controllers\SchoolAdmin\ExamPaperController;
use App\Http\Controllers\StudentCbtController;
use App\Http\Controllers\CandidateCbtController;
use App\Http\Controllers\SchoolAdmin\AiSettingsController;
use App\Http\Controllers\SchoolAdmin\SchoolTypeController;
use App\Http\Controllers\SuperAdmin\AiController as SuperAdminAiController;
use App\Http\Controllers\ParentPaymentController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\SchoolAdmin\OnlinePaymentController;
use App\Http\Controllers\SchoolAdmin\PaymentPlanController;
use App\Http\Controllers\SchoolAdmin\StaffHomeController;
use App\Http\Controllers\SchoolAdmin\HouseController;
use App\Http\Controllers\SchoolAdmin\ClubController;
use App\Http\Controllers\SchoolAdmin\IdCardController;
use App\Http\Controllers\IdCardCheckController;
use App\Http\Controllers\CbtImageController;
use App\Http\Controllers\SchoolAdmin\CommentBankController;
use App\Http\Controllers\SchoolAdmin\ReportCardController;
use App\Http\Controllers\SchoolAdmin\ReportCardExportController;
use App\Http\Controllers\ReportCardPortalController;
use App\Http\Controllers\SchoolAdmin\ReportCardDesignController;
use App\Http\Controllers\CertificateVerifyController;
use App\Http\Controllers\StudentPhotoController;
use App\Http\Controllers\SchoolAdmin\VisitorLogController;
use App\Http\Controllers\PublicAdmissionController;
use App\Http\Controllers\NewsletterPublicController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\SchoolAdmin\Website\WebsiteExtensionController;
use App\Http\Controllers\SchoolAdmin\Website\WebsiteGalleryController;
use App\Http\Controllers\SchoolAdmin\Website\WebsiteController;
use App\Http\Controllers\SchoolAdmin\Website\WebsiteMediaController;
use App\Http\Controllers\SchoolAdmin\Website\WebsiteFormsController;
use App\Http\Controllers\SchoolAdmin\Website\WebsiteNewsletterController;
use App\Http\Controllers\SchoolAdmin\Website\WebsitePageController;
use App\Http\Controllers\SchoolAdmin\Website\WebsitePostController;
use App\Http\Controllers\SchoolAdmin\Website\WebsiteThemeController;
use App\Http\Controllers\SchoolAdmin\Website\WebsiteDeveloperController;
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
    Route::get('/forgot-password', [PasswordResetController::class, 'forgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,10')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:10,10')->name('password.update');
});

// The demo: pick an account type, then look around as that person. Open to visitors and to
// someone already in the demo (to switch account type); a real account is sent to its dashboard.
Route::get('/demo', [DemoController::class, 'pick'])->name('demo');
Route::post('/demo/{account}', [DemoController::class, 'enter'])->middleware('throttle:20,1')->name('demo.enter');

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
            \App\Support\PlatformAccess::isStaff($user)                    => redirect()->route(\App\Support\PlatformAccess::homeRoute($user)),
            $user->hasRole('school-admin') || $user->hasRole('principal')   => redirect()->route('school.reports.dashboard'),
            $user->hasRole('teacher')                                        => redirect()->route('school.my-classes'),
            $user->hasRole('student')                                        => redirect()->route('student.dashboard'),
            $user->hasRole('parent')                                         => redirect()->route('parent.dashboard'),
            $user->can('reports.view')                                       => redirect()->route('school.reports.dashboard'),
            // Front desk, drivers, wardens and store keepers get a home page made of their own work
            default                                                          => redirect()->route('school.home'),
        };
    })->name('dashboard');

    // Student passport photos: the controller checks the viewer (staff of the school, the student, or their parent)
    Route::get('photos/students/{student}', [StudentPhotoController::class, 'show'])->whereNumber('student')->name('students.photo');
    Route::get('cbt/images/{question}', [CbtImageController::class, 'show'])->whereNumber('question')->name('cbt.image');

    /*
    |--------------------------------------------------------------------------
    | School Admin routes (school-admin, principal)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super-admin|platform-support|platform-finance|platform-sales|platform-developer|platform-content|school-admin|principal|teacher|accountant|librarian|receptionist|driver|warden|store-manager')
        ->prefix('school')
        ->name('school.')
        ->group(function () {
            // Home page for staff without the school dashboard; every staff role can apply for leave, so all have leave.view
            Route::get('home', StaffHomeController::class)->middleware('permission:leave.view')->name('home');

            // Sports houses and house points
            Route::get('houses',                         [HouseController::class, 'index'])->middleware('permission:houses.view')->name('houses.index');
            Route::post('houses',                        [HouseController::class, 'store'])->middleware('permission:houses.manage')->name('houses.store');
            Route::get('houses/assign',                  [HouseController::class, 'members'])->middleware('permission:houses.manage')->name('houses.assign');
            Route::post('houses/assign',                 [HouseController::class, 'saveMembers'])->middleware('permission:houses.manage')->name('houses.assign.save');
            Route::post('houses/share',                  [HouseController::class, 'share'])->middleware('permission:houses.manage')->name('houses.share');
            Route::post('houses/points',                 [HouseController::class, 'award'])->middleware('permission:houses.points')->name('houses.points');
            Route::post('houses/points/{point}/undo',    [HouseController::class, 'undo'])->middleware('permission:houses.manage')->name('houses.points.undo');
            Route::put('houses/{house}',                 [HouseController::class, 'update'])->middleware('permission:houses.manage')->name('houses.update');
            Route::delete('houses/{house}',              [HouseController::class, 'destroy'])->middleware('permission:houses.manage')->name('houses.destroy');

            // Clubs and societies
            Route::get('clubs',                          [ClubController::class, 'index'])->middleware('permission:clubs.view')->name('clubs.index');
            Route::post('clubs',                         [ClubController::class, 'store'])->middleware('permission:clubs.manage')->name('clubs.store');
            Route::get('clubs/{club}',                   [ClubController::class, 'show'])->middleware('permission:clubs.view')->name('clubs.show');
            Route::put('clubs/{club}',                   [ClubController::class, 'update'])->middleware('permission:clubs.manage')->name('clubs.update');
            Route::delete('clubs/{club}',                [ClubController::class, 'destroy'])->middleware('permission:clubs.manage')->name('clubs.destroy');
            Route::post('clubs/{club}/members',          [ClubController::class, 'addMembers'])->middleware('permission:clubs.manage')->name('clubs.members.add');
            Route::put('clubs/{club}/members/{member}',  [ClubController::class, 'updateMember'])->middleware('permission:clubs.manage')->name('clubs.members.update');
            Route::delete('clubs/{club}/members/{member}', [ClubController::class, 'removeMember'])->middleware('permission:clubs.manage')->name('clubs.members.remove');

            // Student ID cards: register, printing, designs and the scan screen
            Route::get('id-cards',                       [IdCardController::class, 'index'])->middleware('permission:students.idcard')->name('id-cards.index');
            Route::post('id-cards/issue-missing',        [IdCardController::class, 'issueMissing'])->middleware('permission:students.idcard')->name('id-cards.issue-missing');
            Route::get('id-cards/print',                 [IdCardController::class, 'print'])->middleware('permission:students.idcard')->name('id-cards.print');
            Route::get('id-cards/designs',               [IdCardController::class, 'designs'])->middleware('permission:students.idcard')->name('id-cards.designs');
            Route::post('id-cards/designs',              [IdCardController::class, 'saveDesign'])->middleware('permission:settings.edit')->name('id-cards.designs.store');
            Route::post('id-cards/designs/try',          [IdCardController::class, 'tryDesign'])->middleware('permission:students.idcard')->name('id-cards.designs.try');
            Route::put('id-cards/designs/{design}',      [IdCardController::class, 'saveDesign'])->middleware('permission:settings.edit')->name('id-cards.designs.update');
            Route::delete('id-cards/designs/{design}',   [IdCardController::class, 'deleteDesign'])->middleware('permission:settings.edit')->name('id-cards.designs.destroy');
            Route::get('id-cards/scan',                  [IdCardController::class, 'scanPage'])->middleware('permission:students.view')->name('id-cards.scan');
            Route::post('id-cards/lookup',               [IdCardController::class, 'lookup'])->middleware(['permission:students.view', 'throttle:120,1'])->name('id-cards.lookup');
            Route::post('id-cards/{card}/revoke',        [IdCardController::class, 'revoke'])->middleware('permission:students.idcard')->name('id-cards.revoke');
            Route::post('students/{student}/id-card',    [IdCardController::class, 'issue'])->middleware('permission:students.idcard')->name('students.id-card.issue');
            Route::get('students/{student}/id-card',     [IdCardController::class, 'studentCard'])->middleware('permission:students.idcard')->name('students.id-card');

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

            Route::get('my-classes', [MyClassesController::class, 'index'])->middleware('permission:attendance.view')->name('my-classes');
            Route::put('class-manager-label', [ClassController::class, 'saveLabel'])->middleware('permission:settings.edit')->name('classes.label');
            Route::post('classes/{class}/teachers', [ClassController::class, 'teachers'])->middleware('permission:settings.edit')->name('classes.teachers');

            // Bulk adding of students and bulk sign-in details (before the students/{student} routes below)
            Route::get('students/import',                       [StudentImportController::class, 'index'])->middleware('permission:students.import')->name('students.import');
            Route::get('students/import/template',              [StudentImportController::class, 'template'])->middleware('permission:students.import')->name('students.import.template');
            Route::post('students/import/preview',              [StudentImportController::class, 'preview'])->middleware(['permission:students.import', 'throttle:20,1'])->name('students.import.preview');
            Route::post('students/import',                      [StudentImportController::class, 'store'])->middleware('permission:students.import')->name('students.import.store');
            Route::get('students/logins',                       [StudentLoginController::class, 'index'])->middleware('permission:students.logins')->name('students.logins');
            Route::post('students/logins/start',                [StudentLoginController::class, 'start'])->middleware('permission:students.logins')->name('students.logins.start');
            Route::post('students/logins/{token}/step',         [StudentLoginController::class, 'step'])->middleware(['permission:students.logins', 'throttle:600,1'])->whereUuid('token')->name('students.logins.step');
            Route::get('students/logins/{token}/pdf',           [StudentLoginController::class, 'pdf'])->middleware('permission:students.logins')->whereUuid('token')->name('students.logins.pdf');
            Route::get('students/logins/{token}/excel',         [StudentLoginController::class, 'excel'])->middleware('permission:students.logins')->whereUuid('token')->name('students.logins.excel');
            Route::get('students/renumber',  [StudentRenumberController::class, 'index'])->middleware('permission:students.edit')->name('students.renumber');
            Route::post('students/renumber', [StudentRenumberController::class, 'apply'])->middleware('permission:students.edit')->name('students.renumber.apply');
            Route::resource('students', StudentController::class)->only(['create', 'store'])->middleware('permission:students.create');
            Route::resource('students', StudentController::class)->only(['index', 'show'])->middleware('permission:students.view');
            Route::resource('students', StudentController::class)->only(['edit', 'update'])->middleware('permission:students.edit');
            // Testimonials and transfer certificates
            Route::get('certificates',                         [CertificateController::class, 'index'])->middleware('permission:students.view')->name('certificates.index');
            // Computer-based tests
            Route::get('cbt/banks',                                   [CbtBankController::class, 'index'])->middleware('permission:cbt.view')->name('cbt.banks');
            Route::post('cbt/banks',                                  [CbtBankController::class, 'store'])->middleware('permission:cbt.manage')->name('cbt.banks.store');
            Route::get('cbt/banks/template',                          [CbtBankController::class, 'template'])->middleware('permission:cbt.view')->name('cbt.banks.template');
            Route::get('cbt/banks/{bank}',                            [CbtBankController::class, 'show'])->middleware('permission:cbt.view')->whereNumber('bank')->name('cbt.banks.show');
            Route::put('cbt/banks/{bank}',                            [CbtBankController::class, 'update'])->middleware('permission:cbt.manage')->name('cbt.banks.update');
            Route::delete('cbt/banks/{bank}',                         [CbtBankController::class, 'destroy'])->middleware('permission:cbt.manage')->name('cbt.banks.destroy');
            Route::post('cbt/banks/{bank}/questions',                 [CbtBankController::class, 'storeQuestion'])->middleware('permission:cbt.manage')->name('cbt.questions.store');
            Route::post('cbt/banks/{bank}/questions/many',            [CbtBankController::class, 'storeMany'])->middleware('permission:cbt.manage')->name('cbt.questions.many');
            Route::post('cbt/banks/{bank}/questions/{question}',      [CbtBankController::class, 'updateQuestion'])->middleware('permission:cbt.manage')->name('cbt.questions.update');
            Route::delete('cbt/banks/{bank}/questions/{question}',    [CbtBankController::class, 'destroyQuestion'])->middleware('permission:cbt.manage')->name('cbt.questions.destroy');
            Route::post('cbt/banks/{bank}/ai',                        [CbtBankController::class, 'aiDraft'])->middleware(['permission:cbt.manage', 'throttle:20,1'])->name('cbt.questions.ai');
            Route::post('cbt/banks/{bank}/import',                    [CbtBankController::class, 'import'])->middleware('permission:cbt.manage')->name('cbt.questions.import');
            Route::get('cbt/papers',                                  [ExamPaperController::class, 'index'])->middleware('permission:cbt.view')->name('cbt.papers');
            Route::get('cbt/papers/new',                              [ExamPaperController::class, 'create'])->middleware('permission:cbt.manage')->name('cbt.papers.create');
            Route::get('cbt/papers/questions',                        [ExamPaperController::class, 'bankQuestions'])->middleware('permission:cbt.view')->name('cbt.papers.questions');
            Route::post('cbt/papers',                                 [ExamPaperController::class, 'store'])->middleware('permission:cbt.manage')->name('cbt.papers.store');
            Route::get('cbt/papers/{paper}',                          [ExamPaperController::class, 'show'])->middleware('permission:cbt.view')->whereNumber('paper')->name('cbt.papers.show');
            Route::put('cbt/papers/{paper}',                          [ExamPaperController::class, 'update'])->middleware('permission:cbt.manage')->name('cbt.papers.update');
            Route::delete('cbt/papers/{paper}',                       [ExamPaperController::class, 'destroy'])->middleware('permission:cbt.manage')->name('cbt.papers.destroy');
            Route::get('cbt/papers/{paper}/pdf',                      [ExamPaperController::class, 'pdf'])->middleware('permission:cbt.view')->name('cbt.papers.pdf');
            Route::get('cbt/papers/{paper}/marking-scheme',           [ExamPaperController::class, 'markingScheme'])->middleware('permission:cbt.view')->name('cbt.papers.scheme');
            Route::get('cbt/taught',                                  [CbtTestController::class, 'taught'])->middleware('permission:cbt.view')->name('cbt.taught');
            Route::get('cbt/tests',                                   [CbtTestController::class, 'index'])->middleware('permission:cbt.view')->name('cbt.tests');
            Route::get('cbt/tests/new',                               [CbtTestController::class, 'create'])->middleware('permission:cbt.manage')->name('cbt.tests.create');
            Route::post('cbt/tests',                                  [CbtTestController::class, 'store'])->middleware('permission:cbt.manage')->name('cbt.tests.store');
            Route::get('cbt/tests/{test}',                            [CbtTestController::class, 'show'])->middleware('permission:cbt.view')->whereNumber('test')->name('cbt.tests.show');
            Route::get('cbt/tests/{test}/edit',                       [CbtTestController::class, 'edit'])->middleware('permission:cbt.manage')->name('cbt.tests.edit');
            Route::put('cbt/tests/{test}',                            [CbtTestController::class, 'update'])->middleware('permission:cbt.manage')->name('cbt.tests.update');
            Route::delete('cbt/tests/{test}',                         [CbtTestController::class, 'destroy'])->middleware('permission:cbt.manage')->name('cbt.tests.destroy');
            Route::post('cbt/tests/{test}/status',                    [CbtTestController::class, 'status'])->middleware('permission:cbt.manage')->name('cbt.tests.status');
            Route::get('cbt/tests/{test}/export',                     [CbtTestController::class, 'export'])->middleware('permission:cbt.view')->name('cbt.tests.export');
            Route::get('cbt/tests/{test}/attempts/{attempt}',         [CbtTestController::class, 'attempt'])->middleware('permission:cbt.view')->whereNumber(['test','attempt'])->name('cbt.attempts.show');
            Route::post('cbt/tests/{test}/attempts/{attempt}/time',   [CbtTestController::class, 'addTime'])->middleware('permission:cbt.manage')->name('cbt.attempts.time');
            Route::delete('cbt/tests/{test}/attempts/{attempt}',      [CbtTestController::class, 'reset'])->middleware('permission:cbt.manage')->name('cbt.attempts.reset');
            Route::get('cbt/tests/{test}/marking',                    [CbtTestController::class, 'marking'])->middleware('permission:cbt.manage')->name('cbt.tests.marking');
            Route::post('cbt/tests/{test}/marking',                   [CbtTestController::class, 'saveMarks'])->middleware('permission:cbt.manage')->name('cbt.tests.marks');
            Route::post('cbt/tests/{test}/copy-to-results',           [CbtTestController::class, 'copyToResults'])->middleware('permission:marks.entry')->name('cbt.tests.copy');
            Route::post('cbt/tests/{test}/candidates',                [CbtTestController::class, 'addCandidates'])->middleware('permission:cbt.manage')->name('cbt.candidates.store');
            Route::delete('cbt/tests/{test}/candidates/{candidate}',  [CbtTestController::class, 'removeCandidate'])->middleware('permission:cbt.manage')->name('cbt.candidates.destroy');
            Route::post('cbt/tests/{test}/candidates/{candidate}/pin', [CbtTestController::class, 'newPin'])->middleware('permission:cbt.manage')->name('cbt.candidates.pin');
            Route::get('cbt/tests/{test}/slips',                      [CbtTestController::class, 'slips'])->middleware('permission:cbt.view')->name('cbt.tests.slips');

            Route::get('certificates/designs',                 [CertificateDesignController::class, 'index'])->middleware('permission:students.certificates')->name('certificate-designs.index');
            Route::post('certificates/designs/{type}',         [CertificateDesignController::class, 'update'])->whereIn('type', ['testimonial', 'transfer'])->middleware('permission:settings.edit')->name('certificate-designs.update');
            Route::get('certificates/designs/{type}/preview',  [CertificateDesignController::class, 'preview'])->whereIn('type', ['testimonial', 'transfer'])->middleware('permission:students.certificates')->name('certificate-designs.preview');
            Route::post('certificates/designs/{type}/try',     [CertificateDesignController::class, 'tryOut'])->whereIn('type', ['testimonial', 'transfer'])->middleware('permission:students.certificates')->name('certificate-designs.try');
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
            Route::post('promotions/{promotionBatch}/bill-fees', [PromotionController::class, 'billFees'])->middleware('permission:fees.structure')->name('promotions.bill-fees');
            Route::post('promotions/{promotionBatch}/undo', [PromotionController::class, 'undo'])->middleware('permission:students.promote')->name('promotions.undo');

            // Exams
            Route::get('exams',                              [ExamController::class, 'index'])->middleware('permission:exams.view')->name('exams.index');
            Route::post('exams',                             [ExamController::class, 'store'])->middleware('permission:exams.create')->name('exams.store');
            Route::put('exams/{exam}',                       [ExamController::class, 'update'])->middleware('permission:exams.edit')->name('exams.update');
            Route::delete('exams/{exam}',                    [ExamController::class, 'destroy'])->middleware('permission:exams.delete')->name('exams.destroy');
            Route::get('exams/{exam}/marks',                 [ExamController::class, 'marks'])->middleware('permission:marks.view')->name('exams.marks');
            Route::post('exams/{exam}/marks',                [ExamController::class, 'saveMarks'])->middleware('permission:marks.entry')->name('exams.marks.save');
            Route::get('exams/{exam}/results',               [ExamController::class, 'results'])->middleware('permission:results.view')->name('exams.results');
            Route::post('exams/{exam}/copy-to-results',      [ExamController::class, 'copyToResults'])->middleware('permission:marks.entry')->name('exams.copy-to-results');
            // School years, terms, score setups and grade scales
            Route::get('new-term',                             [NewTermController::class, 'index'])->middleware('permission:exams.view')->name('new-term');
            Route::post('new-term/tick',                       [NewTermController::class, 'toggle'])->middleware('permission:exams.view')->name('new-term.tick');
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
            Route::post('academics/report-card-designs/{design}/try',     [ReportCardDesignController::class, 'tryOut'])->middleware('permission:reportcard.generate')->name('report-card-designs.try');
            Route::get('academics/report-card-designs/{design}/thumb',    [ReportCardDesignController::class, 'thumb'])->middleware('permission:reportcard.generate')->name('report-card-designs.thumb');
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
            Route::post('results/{sheet}/comments/ai',           [ReportCardController::class, 'aiComments'])->middleware(['permission:results.view', 'throttle:20,1'])->name('results.comments.ai');
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
            Route::put('hostel/allocations/{allocation}',                 [HostelController::class, 'updateAllocation'])->middleware('permission:hostel.manage')->name('hostel.allocations.update');
            Route::get('hostel/student-search',                           [HostelController::class, 'studentSearch'])->middleware('permission:hostel.view')->name('hostel.student-search');
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

            // Lesson plans became lesson notes; the old address still works
            Route::get('homework/lesson-plans', fn () => redirect()->route('school.lesson-notes.index'))->middleware('permission:lessons.view')->name('homework.lesson-plans.index');

            // Lesson notes, schemes of work and note formats
            Route::get('lesson-notes',                                [LessonNoteController::class, 'index'])->middleware('permission:lessons.view')->name('lesson-notes.index');
            Route::get('lesson-notes/new',                            [LessonNoteController::class, 'create'])->middleware('permission:lessons.create')->name('lesson-notes.create');
            Route::post('lesson-notes',                               [LessonNoteController::class, 'store'])->middleware('permission:lessons.create')->name('lesson-notes.store');
            Route::post('lesson-notes/ai',                            [LessonNoteController::class, 'aiDraft'])->middleware(['permission:lessons.create', 'throttle:20,1'])->name('lesson-notes.ai');
            Route::post('lesson-notes/read-note',                     [LessonNoteController::class, 'readNote'])->middleware(['permission:lessons.create', 'throttle:20,1'])->name('lesson-notes.read-note');
            Route::get('lesson-notes/schemes',                        [LessonNoteController::class, 'schemes'])->middleware('permission:lessons.view')->name('lesson-notes.schemes');
            Route::get('lesson-notes/schemes/new',                    [LessonNoteController::class, 'scheme'])->middleware('permission:lessons.create')->name('lesson-notes.schemes.create');
            Route::post('lesson-notes/schemes',                       [LessonNoteController::class, 'storeScheme'])->middleware('permission:lessons.create')->name('lesson-notes.schemes.store');
            Route::post('lesson-notes/schemes/read',                  [LessonNoteController::class, 'readScheme'])->middleware(['permission:lessons.create', 'throttle:20,1'])->name('lesson-notes.schemes.read');
            Route::get('lesson-notes/schemes/{scheme}',               [LessonNoteController::class, 'scheme'])->middleware('permission:lessons.view')->name('lesson-notes.schemes.show');
            Route::post('lesson-notes/schemes/{scheme}',              [LessonNoteController::class, 'updateScheme'])->middleware('permission:lessons.create')->name('lesson-notes.schemes.update');
            Route::delete('lesson-notes/schemes/{scheme}',            [LessonNoteController::class, 'destroyScheme'])->middleware('permission:lessons.create')->name('lesson-notes.schemes.destroy');
            Route::get('lesson-notes/schemes/{scheme}/file',          [LessonNoteController::class, 'schemeFile'])->middleware('permission:lessons.view')->name('lesson-notes.schemes.file');
            Route::get('lesson-notes/formats',                        [LessonNoteController::class, 'formats'])->middleware('permission:lessons.view')->name('lesson-notes.formats');
            Route::post('lesson-notes/formats',                       [LessonNoteController::class, 'storeFormat'])->middleware('permission:lessons.approve')->name('lesson-notes.formats.store');
            Route::put('lesson-notes/formats/{format}',               [LessonNoteController::class, 'updateFormat'])->middleware('permission:lessons.approve')->name('lesson-notes.formats.update');
            Route::delete('lesson-notes/formats/{format}',            [LessonNoteController::class, 'destroyFormat'])->middleware('permission:lessons.approve')->name('lesson-notes.formats.destroy');
            Route::get('lesson-notes/{note}',                         [LessonNoteController::class, 'edit'])->middleware('permission:lessons.view')->name('lesson-notes.edit');
            Route::put('lesson-notes/{note}',                         [LessonNoteController::class, 'update'])->middleware('permission:lessons.create')->name('lesson-notes.update');
            Route::put('lesson-notes/{note}/review',                  [LessonNoteController::class, 'review'])->middleware('permission:lessons.approve')->name('lesson-notes.review');
            Route::delete('lesson-notes/{note}',                      [LessonNoteController::class, 'destroy'])->middleware('permission:lessons.create')->name('lesson-notes.destroy');
            Route::get('lesson-notes/{note}/pdf',                     [LessonNoteController::class, 'pdf'])->middleware('permission:lessons.view')->name('lesson-notes.pdf');

            Route::get('homework/syllabi',                            [HomeworkController::class, 'syllabi'])->middleware('permission:syllabus.view')->name('homework.syllabi.index');
            Route::post('homework/syllabi/read',                      [HomeworkController::class, 'readSyllabus'])->middleware(['permission:syllabus.manage', 'throttle:20,1'])->name('homework.syllabi.read');
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
            Route::post('fees/structures/many',              [FeeStructureController::class, 'storeMany'])->middleware('permission:fees.structure')->name('fees.structures.many');
            Route::put('fees/structures/{feeStructure}',     [FeeStructureController::class, 'update'])->middleware('permission:fees.structure')->name('fees.structures.update');
            Route::delete('fees/structures/{feeStructure}',  [FeeStructureController::class, 'destroy'])->middleware('permission:fees.structure')->name('fees.structures.destroy');

            Route::get('fees/payments',                      [FeePaymentController::class, 'index'])->middleware('permission:fees.view')->name('fees.payments.index');
            Route::get('fees/payments/collect',              [FeePaymentController::class, 'create'])->middleware('permission:fees.collect')->name('fees.payments.create');
            Route::post('fees/payments',                     [FeePaymentController::class, 'store'])->middleware('permission:fees.collect')->name('fees.payments.store');
            Route::get('fees/payments/{feePayment}',         [FeePaymentController::class, 'show'])->middleware('permission:fees.view')->name('fees.payments.show');
            // Invoices and the fee ledger (lines are never edited; mistakes are reversed)
            Route::get('fees/invoices',                      [FeeInvoiceController::class, 'index'])->middleware('permission:fees.view')->name('fees.invoices.index');
            Route::post('fees/invoices',                     [FeeInvoiceController::class, 'issue'])->middleware('permission:fees.structure')->name('fees.invoices.issue');
            Route::post('fees/invoices/plans',               [FeeInvoiceController::class, 'planMany'])->middleware('permission:fees.structure')->name('fees.invoices.plan-many');
            Route::post('fees/invoices/{invoice}/plan',      [FeeInvoiceController::class, 'plan'])->middleware('permission:fees.structure')->name('fees.invoices.plan');
            Route::delete('fees/invoices/{invoice}/plan',    [FeeInvoiceController::class, 'unplan'])->middleware('permission:fees.structure')->name('fees.invoices.unplan');
            // Saved ways of paying a fee in parts
            Route::get('fees/payment-plans',                 [PaymentPlanController::class, 'index'])->middleware('permission:fees.view')->name('fees.plans.index');
            Route::post('fees/payment-plans',                [PaymentPlanController::class, 'store'])->middleware('permission:fees.structure')->name('fees.plans.store');
            Route::put('fees/payment-plans/{plan}',          [PaymentPlanController::class, 'update'])->middleware('permission:fees.structure')->name('fees.plans.update');
            Route::delete('fees/payment-plans/{plan}',       [PaymentPlanController::class, 'destroy'])->middleware('permission:fees.structure')->name('fees.plans.destroy');
            Route::get('fees/invoices/{invoice}',            [FeeInvoiceController::class, 'show'])->middleware('permission:fees.view')->name('fees.invoices.show');
            Route::post('fees/invoices/{invoice}/payments',  [FeeInvoiceController::class, 'pay'])->middleware('permission:fees.collect')->name('fees.invoices.pay');
            Route::post('fees/invoices/{invoice}/fines',     [FeeInvoiceController::class, 'fine'])->middleware('permission:fees.collect')->name('fees.invoices.fine');
            Route::post('fees/invoices/{invoice}/entries/{entry}/reverse', [FeeInvoiceController::class, 'reverse'])->middleware('permission:fees.waiver')->name('fees.invoices.reverse');
            Route::post('fees/invoices/{invoice}/refunds',   [FeeInvoiceController::class, 'refund'])->middleware('permission:fees.waiver')->name('fees.invoices.refund');
            Route::post('fees/invoices/{invoice}/void',      [FeeInvoiceController::class, 'void'])->middleware('permission:fees.waiver')->name('fees.invoices.void');

            // Named scholarships and discounts, with who approved each one
            Route::get('fees/online-payments',               [OnlinePaymentController::class, 'index'])->middleware('permission:fees.view')->name('fees.online.index');
            Route::post('fees/online-payments/setup',        [OnlinePaymentController::class, 'saveSetup'])->middleware('permission:settings.edit')->name('fees.online.setup');
            Route::post('fees/online-payments/{payment}/check', [OnlinePaymentController::class, 'check'])->middleware('permission:fees.collect')->name('fees.online.check');
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

            Route::get('communication/sent',                                       [MessageLogController::class, 'index'])->middleware('permission:sms.send|email.send')->name('communication.sent');
            Route::get('communication/sent/{batch}',                               [MessageLogController::class, 'show'])->middleware('permission:sms.send|email.send')->name('communication.sent.show');
            Route::post('communication/sent/{batch}/retry',                        [MessageLogController::class, 'retry'])->middleware('permission:sms.send|email.send')->name('communication.sent.retry');

            Route::get('communication/email-templates',                            [CommunicationController::class, 'emailTemplates'])->middleware('permission:email.send')->name('communication.email-templates');
            Route::post('communication/email-templates',                           [CommunicationController::class, 'storeEmailTemplate'])->middleware('permission:email.send')->name('communication.email-templates.store');
            Route::put('communication/email-templates/{emailTemplate}',            [CommunicationController::class, 'updateEmailTemplate'])->middleware('permission:email.send')->name('communication.email-templates.update');

            Route::get('communication/notifications',                              [CommunicationController::class, 'notifications'])->name('communication.notifications');
            Route::get('communication/notifications/feed',                         [\App\Http\Controllers\NotificationFeedController::class, 'index'])->name('communication.notifications.feed');
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
            Route::get('settings/all',                          [SchoolSettingsController::class, 'hub'])->middleware('permission:settings.view')->name('settings.hub');
            Route::get('settings',                              [SchoolSettingsController::class, 'index'])->middleware('permission:settings.view')->name('settings.index');
            Route::post('settings/general',                     [SchoolSettingsController::class, 'saveGeneral'])->middleware('permission:settings.edit')->name('settings.general');
            Route::post('settings/branding',                    [SchoolSettingsController::class, 'saveBranding'])->middleware('permission:settings.edit')->name('settings.branding');
            Route::post('settings/brand-image',                 [SchoolSettingsController::class, 'saveBrandImage'])->middleware('permission:settings.edit')->name('settings.brand-image');
            Route::post('settings/academic',                    [SchoolSettingsController::class, 'saveAcademic'])->middleware('permission:settings.edit')->name('settings.academic');
            Route::post('settings/notifications',               [SchoolSettingsController::class, 'saveNotifications'])->middleware('permission:settings.edit')->name('settings.notifications');
            Route::get('settings/ai',                           [AiSettingsController::class, 'index'])->middleware('permission:settings.view')->name('settings.ai');
            Route::post('settings/ai',                          [AiSettingsController::class, 'save'])->middleware('permission:settings.edit')->name('settings.ai.save');
            Route::get('settings/school-type',                  [SchoolTypeController::class, 'index'])->middleware('permission:settings.view')->name('settings.school-type');
            Route::post('settings/school-type',                 [SchoolTypeController::class, 'apply'])->middleware('permission:settings.edit')->name('settings.school-type.apply');

            // Public website: the editor (the site itself is served at /s/{school})
            Route::prefix('website')->name('website.')->group(function () {
                $view = 'permission:settings.view';
                $edit = 'permission:settings.edit';
                Route::get('/',                        [WebsiteController::class, 'overview'])->middleware($view)->name('index');
                Route::get('settings',                 [WebsiteController::class, 'settings'])->middleware($view)->name('settings');
                Route::put('/',                        [WebsiteController::class, 'update'])->middleware($edit)->name('update');

                Route::get('themes',                   [WebsiteThemeController::class, 'index'])->middleware($view)->name('themes');
                Route::get('themes/{theme}/sample',    [WebsiteThemeController::class, 'sample'])->where('theme', '[a-z0-9-]+')->middleware($view)->name('themes.sample');
                Route::put('themes',                   [WebsiteThemeController::class, 'update'])->middleware($edit)->name('themes.update');

                Route::get('extensions',               [WebsiteExtensionController::class, 'index'])->middleware($view)->name('extensions');
                Route::post('extensions/{key}',        [WebsiteExtensionController::class, 'toggle'])->where('key', '[a-z_]+')->middleware($edit)->name('extensions.toggle');
                Route::get('extensions/{key}/open',    [WebsiteExtensionController::class, 'setup'])->where('key', '[a-z_]+')->middleware($view)->name('extensions.setup');

                // Newsletter extension
                $nlView = 'permission:newsletter.view';
                $nlSend = 'permission:newsletter.send';
                Route::get('newsletter',                          [WebsiteNewsletterController::class, 'index'])->middleware($nlView)->name('newsletter');
                Route::get('newsletter/export',                   [WebsiteNewsletterController::class, 'export'])->middleware($nlView)->name('newsletter.export');
                Route::post('newsletter/subscribers/{subscriber}/remove', [WebsiteNewsletterController::class, 'removeSubscriber'])->whereNumber('subscriber')->middleware($nlSend)->name('newsletter.subscribers.remove');
                Route::post('newsletter/settings',                [WebsiteNewsletterController::class, 'saveSettings'])->middleware($edit)->name('newsletter.settings');
                Route::post('newsletter/campaigns',               [WebsiteNewsletterController::class, 'storeCampaign'])->middleware($nlSend)->name('newsletter.campaigns.store');
                Route::post('newsletter/preview',                 [WebsiteNewsletterController::class, 'preview'])->middleware($nlView)->name('newsletter.preview');
                Route::get('newsletter/campaigns/{campaign}',     [WebsiteNewsletterController::class, 'campaign'])->whereNumber('campaign')->middleware($nlView)->name('newsletter.campaigns.show');
                Route::post('newsletter/campaigns/{campaign}',    [WebsiteNewsletterController::class, 'updateCampaign'])->whereNumber('campaign')->middleware($nlSend)->name('newsletter.campaigns.update');
                Route::post('newsletter/campaigns/{campaign}/test',  [WebsiteNewsletterController::class, 'sendTest'])->whereNumber('campaign')->middleware($nlSend)->name('newsletter.campaigns.test');
                Route::post('newsletter/campaigns/{campaign}/send',  [WebsiteNewsletterController::class, 'send'])->whereNumber('campaign')->middleware($nlSend)->name('newsletter.campaigns.send');
                Route::post('newsletter/campaigns/{campaign}/step',  [WebsiteNewsletterController::class, 'step'])->whereNumber('campaign')->middleware($nlSend)->name('newsletter.campaigns.step');
                Route::post('newsletter/campaigns/{campaign}/retry', [WebsiteNewsletterController::class, 'retry'])->whereNumber('campaign')->middleware($nlSend)->name('newsletter.campaigns.retry');
                Route::post('newsletter/campaigns/{campaign}/delete', [WebsiteNewsletterController::class, 'destroyCampaign'])->whereNumber('campaign')->middleware($nlSend)->name('newsletter.campaigns.destroy');

                // custom forms (Forms extension)
                $fView = 'permission:forms.view';
                $fManage = 'permission:forms.manage';
                Route::get('forms',                                [WebsiteFormsController::class, 'index'])->middleware($fView)->name('forms');
                Route::post('forms',                               [WebsiteFormsController::class, 'store'])->middleware($fManage)->name('forms.store');
                Route::get('forms/{form}',                         [WebsiteFormsController::class, 'edit'])->whereNumber('form')->middleware($fView)->name('forms.edit');
                Route::post('forms/{form}',                        [WebsiteFormsController::class, 'update'])->whereNumber('form')->middleware($fManage)->name('forms.update');
                Route::post('forms/{form}/delete',                 [WebsiteFormsController::class, 'destroy'])->whereNumber('form')->middleware($fManage)->name('forms.destroy');
                Route::get('forms/{form}/answers',                 [WebsiteFormsController::class, 'answers'])->whereNumber('form')->middleware($fView)->name('forms.answers');
                Route::get('forms/{form}/answers/{submission}/file/{key}', [WebsiteFormsController::class, 'file'])->whereNumber('form')->whereNumber('submission')->where('key', 'f[a-z0-9]{4,10}')->middleware($fView)->name('forms.answers.file');
                Route::get('forms/{form}/export',                  [WebsiteFormsController::class, 'export'])->whereNumber('form')->middleware($fView)->name('forms.export');
                Route::post('forms/{form}/answers/read-all',       [WebsiteFormsController::class, 'markAllRead'])->whereNumber('form')->middleware($fManage)->name('forms.answers.readall');
                Route::post('forms/{form}/answers/{submission}/read',   [WebsiteFormsController::class, 'markRead'])->whereNumber('form')->whereNumber('submission')->middleware($fManage)->name('forms.answers.read');
                Route::post('forms/{form}/answers/{submission}/delete', [WebsiteFormsController::class, 'removeAnswer'])->whereNumber('form')->whereNumber('submission')->middleware($fManage)->name('forms.answers.delete');

                Route::get('developer',                [WebsiteDeveloperController::class, 'index'])->middleware($view)->name('developer');
                Route::put('developer/settings',       [WebsiteDeveloperController::class, 'saveSettings'])->middleware($edit)->name('developer.settings');
                Route::post('developer/webhook',       [WebsiteDeveloperController::class, 'saveWebhook'])->middleware($edit)->name('developer.webhook');
                Route::post('developer/webhook/test',  [WebsiteDeveloperController::class, 'testWebhook'])->middleware($edit)->name('developer.webhook.test');
                Route::post('developer/webhook/remove', [WebsiteDeveloperController::class, 'removeWebhook'])->middleware($edit)->name('developer.webhook.remove');
                Route::post('developer/tokens',        [WebsiteDeveloperController::class, 'makeToken'])->middleware($edit)->name('developer.tokens.make');
                Route::post('developer/tokens/{token}/revoke', [WebsiteDeveloperController::class, 'revokeToken'])->whereNumber('token')->middleware($edit)->name('developer.tokens.revoke');
                Route::get('export',                   [WebsiteDeveloperController::class, 'export'])->middleware($view)->name('export');
                Route::post('import',                  [WebsiteDeveloperController::class, 'import'])->middleware($edit)->name('import');

                Route::get('pages',                    [WebsitePageController::class, 'index'])->middleware($view)->name('pages.index');
                Route::post('pages',                   [WebsitePageController::class, 'store'])->middleware($edit)->name('pages.store');
                Route::put('pages/order',              [WebsitePageController::class, 'reorder'])->middleware($edit)->name('pages.reorder');
                Route::get('pages/{page}',             [WebsitePageController::class, 'edit'])->whereNumber('page')->middleware($view)->name('pages.edit');
                Route::put('pages/{page}',             [WebsitePageController::class, 'update'])->whereNumber('page')->middleware($edit)->name('pages.update');
                Route::patch('pages/{page}',           [WebsitePageController::class, 'toggle'])->whereNumber('page')->middleware($edit)->name('pages.toggle');
                Route::post('pages/{page}/copy',       [WebsitePageController::class, 'duplicate'])->whereNumber('page')->middleware($edit)->name('pages.copy');
                Route::delete('pages/{page}',          [WebsitePageController::class, 'destroy'])->whereNumber('page')->middleware($edit)->name('pages.destroy');
                Route::post('preview',                 [WebsitePageController::class, 'preview'])->middleware($view)->name('preview');

                Route::get('posts',                    [WebsitePostController::class, 'index'])->middleware($view)->name('posts.index');
                Route::get('posts/create',             [WebsitePostController::class, 'create'])->middleware($edit)->name('posts.create');
                Route::post('posts',                   [WebsitePostController::class, 'store'])->middleware($edit)->name('posts.store');
                Route::post('posts/preview',           [WebsitePostController::class, 'preview'])->middleware($view)->name('posts.preview');
                Route::get('posts/{post}',             [WebsitePostController::class, 'edit'])->whereNumber('post')->middleware($view)->name('posts.edit');
                Route::put('posts/{post}',             [WebsitePostController::class, 'update'])->whereNumber('post')->middleware($edit)->name('posts.update');
                Route::delete('posts/{post}',          [WebsitePostController::class, 'destroy'])->whereNumber('post')->middleware($edit)->name('posts.destroy');

                Route::get('gallery',                  [WebsiteGalleryController::class, 'index'])->middleware($view)->name('gallery.index');
                Route::post('gallery',                 [WebsiteGalleryController::class, 'store'])->middleware($edit)->name('gallery.store');
                Route::get('gallery/{album}',          [WebsiteGalleryController::class, 'edit'])->whereNumber('album')->middleware($view)->name('gallery.edit');
                Route::put('gallery/{album}',          [WebsiteGalleryController::class, 'update'])->whereNumber('album')->middleware($edit)->name('gallery.update');
                Route::delete('gallery/{album}',       [WebsiteGalleryController::class, 'destroy'])->whereNumber('album')->middleware($edit)->name('gallery.destroy');
                Route::post('gallery/{album}/photos',  [WebsiteGalleryController::class, 'addPhotos'])->whereNumber('album')->middleware($edit)->name('gallery.photos.add');
                Route::put('gallery/{album}/order',    [WebsiteGalleryController::class, 'reorder'])->whereNumber('album')->middleware($edit)->name('gallery.photos.order');
                Route::put('gallery/{album}/cover',    [WebsiteGalleryController::class, 'cover'])->whereNumber('album')->middleware($edit)->name('gallery.cover');
                Route::put('gallery/{album}/photos/{photo}',    [WebsiteGalleryController::class, 'updatePhoto'])->whereNumber(['album', 'photo'])->middleware($edit)->name('gallery.photos.update');
                Route::delete('gallery/{album}/photos/{photo}', [WebsiteGalleryController::class, 'removePhoto'])->whereNumber(['album', 'photo'])->middleware($edit)->name('gallery.photos.remove');

                Route::get('media',                    [WebsiteMediaController::class, 'index'])->middleware($view)->name('media.index');
                Route::post('media',                   [WebsiteMediaController::class, 'store'])->middleware($edit)->name('media.store');
                Route::post('images',                  [WebsiteMediaController::class, 'store'])->middleware($edit)->name('images'); // older editor address
                Route::put('media/{media}',            [WebsiteMediaController::class, 'update'])->whereNumber('media')->middleware($edit)->name('media.update');
                Route::delete('media/{media}',         [WebsiteMediaController::class, 'destroy'])->whereNumber('media')->middleware($edit)->name('media.destroy');
            });

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
            Route::post('staff/{staff}/login',             [StaffController::class, 'giveLogin'])->middleware('permission:users.create')->name('staff.login.give');
            Route::post('staff/{staff}/login/reset',       [StaffController::class, 'resetLogin'])->middleware('permission:users.edit')->name('staff.login.reset');
            Route::put('staff/{staff}/assignments',        [StaffController::class, 'assign'])->middleware('permission:settings.edit')->name('staff.assign');
            Route::post('staff/{staff}/website',           [StaffController::class, 'toggleWebsite'])->middleware('permission:staff.edit')->name('staff.website');
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
        Route::get('cbt',                   [StudentCbtController::class, 'index'])->name('cbt');
        Route::get('cbt/{test}',            [StudentCbtController::class, 'show'])->whereNumber('test')->name('cbt.show');
        Route::post('cbt/{test}/start',     [StudentCbtController::class, 'start'])->middleware('throttle:20,1')->name('cbt.start');
        Route::get('cbt/{test}/exam',       [StudentCbtController::class, 'exam'])->name('cbt.exam');
        Route::post('cbt/{test}/answer',    [StudentCbtController::class, 'answer'])->middleware('throttle:240,1')->name('cbt.answer');
        Route::post('cbt/{test}/flag',      [StudentCbtController::class, 'flag'])->middleware('throttle:240,1')->name('cbt.flag');
        Route::post('cbt/{test}/left',      [StudentCbtController::class, 'left'])->middleware('throttle:60,1')->name('cbt.left');
        Route::post('cbt/{test}/ping',      [StudentCbtController::class, 'ping'])->middleware('throttle:60,1')->name('cbt.ping');
        Route::post('cbt/{test}/submit',    [StudentCbtController::class, 'submit'])->name('cbt.submit');
        Route::get('cbt/{test}/done',       [StudentCbtController::class, 'done'])->name('cbt.done');
        Route::get('homework',      [StudentPortalController::class, 'homework'])->name('homework');
        Route::get('fees',          [StudentPortalController::class, 'fees'])->name('fees');
        Route::get('announcements', [StudentPortalController::class, 'announcements'])->name('announcements');
        Route::get('notifications/feed', [\App\Http\Controllers\NotificationFeedController::class, 'index'])->name('notifications.feed');
    });

    Route::middleware('role:parent')->prefix('school/parent')->name('parent.')->group(function () {
        Route::get('dashboard',     [ParentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('attendance',    [ParentPortalController::class, 'attendance'])->name('attendance');
        Route::get('results',       [ParentPortalController::class, 'results'])->name('results');
        Route::get('report-cards/{student}/{sheet}',         [ReportCardPortalController::class, 'parentTerm'])->whereNumber(['student', 'sheet'])->name('report-cards.term');
        Route::get('report-cards/{student}/{sheet}/session', [ReportCardPortalController::class, 'parentSession'])->whereNumber(['student', 'sheet'])->name('report-cards.session');
        Route::get('fees',          [ParentPortalController::class, 'fees'])->name('fees');
        Route::post('fees/invoices/{invoice}/pay', [ParentPaymentController::class, 'pay'])->middleware('throttle:10,1')->name('fees.pay');
        Route::get('fees/pay/{reference}/go',     [ParentPaymentController::class, 'go'])->name('fees.pay.go');
        Route::get('fees/pay/{reference}/result', [ParentPaymentController::class, 'result'])->name('fees.pay.result');
        Route::get('announcements', [ParentPortalController::class, 'announcements'])->name('announcements');
        Route::get('messages',      [\App\Http\Controllers\ParentContactController::class, 'index'])->name('messages');
        Route::post('messages',     [\App\Http\Controllers\ParentContactController::class, 'send'])->middleware('throttle:10,1')->name('messages.send');
        Route::post('absent',       [\App\Http\Controllers\ParentContactController::class, 'absent'])->middleware('throttle:10,1')->name('absent');
        Route::get('notifications/feed', [\App\Http\Controllers\NotificationFeedController::class, 'index'])->name('notifications.feed');
    });

    /*
    |--------------------------------------------------------------------------
    | Super Admin routes
    |--------------------------------------------------------------------------
    */
    // Who may open which page here is decided in one place: App\Support\PlatformAccess::PAGES (owner passes; a page not listed there is owner only)
    Route::middleware('platform')
        ->prefix('super-admin')
        ->name('super-admin.')
        ->group(function () {
            Route::resource('schools', SchoolController::class);
            Route::post('schools/{school}/enter', [SchoolController::class, 'enter'])->name('schools.enter');
            Route::post('schools/{school}/admins', [SchoolController::class, 'addAdmin'])->name('schools.admins.add');
            Route::post('leave-school', [SchoolController::class, 'leave'])->name('schools.leave');
            Route::patch('schools/{school}/suspend', [SchoolController::class, 'suspend'])->name('schools.suspend');
            Route::patch('schools/{school}/activate', [SchoolController::class, 'activate'])->name('schools.activate');

            // School 360: tags, account manager and private notes
            Route::patch('schools/{school}/profile',              [\App\Http\Controllers\SuperAdmin\SchoolSupportController::class, 'profile'])->name('schools.profile');
            Route::post('schools/{school}/notes',                 [\App\Http\Controllers\SuperAdmin\SchoolSupportController::class, 'storeNote'])->name('schools.notes.store');
            Route::patch('schools/{school}/notes/{note}/pin',     [\App\Http\Controllers\SuperAdmin\SchoolSupportController::class, 'togglePin'])->name('schools.notes.pin');
            Route::delete('schools/{school}/notes/{note}',        [\App\Http\Controllers\SuperAdmin\SchoolSupportController::class, 'destroyNote'])->name('schools.notes.destroy');

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

            // Developer tools (each can be switched off in .env, see config/platform_tools.php)
            if (\App\Support\PlatformTools::enabled('health')) {
                Route::get('health', [\App\Http\Controllers\SuperAdmin\HealthController::class, 'index'])->name('health');
            }

            // AI (Gemini)
            Route::get('ai',                                [SuperAdminAiController::class, 'index'])->name('ai.index');
            Route::post('ai',                               [SuperAdminAiController::class, 'save'])->name('ai.save');
            Route::post('ai/check',                         [SuperAdminAiController::class, 'check'])->name('ai.check');
            Route::post('ai/schools/{school}',              [SuperAdminAiController::class, 'saveSchool'])->name('ai.school');

            // Newsletter: the platform's own email service and who may use it
            Route::get('newsletter',            [\App\Http\Controllers\SuperAdmin\NewsletterProviderController::class, 'index'])->name('newsletter.index');
            Route::post('newsletter',           [\App\Http\Controllers\SuperAdmin\NewsletterProviderController::class, 'save'])->name('newsletter.save');

            // Website extensions per plan
            Route::get('extensions',            [\App\Http\Controllers\SuperAdmin\ExtensionController::class, 'index'])->name('extensions.index');
            Route::post('extensions',           [\App\Http\Controllers\SuperAdmin\ExtensionController::class, 'save'])->name('extensions.save');

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

            // Platform staff: the platform's own team (owner only)
            Route::get('staff',                          [\App\Http\Controllers\SuperAdmin\PlatformStaffController::class, 'index'])->name('staff.index');
            Route::post('staff',                         [\App\Http\Controllers\SuperAdmin\PlatformStaffController::class, 'store'])->name('staff.store');
            Route::put('staff/{user}',                   [\App\Http\Controllers\SuperAdmin\PlatformStaffController::class, 'update'])->name('staff.update');
            Route::patch('staff/{user}/suspend',         [\App\Http\Controllers\SuperAdmin\PlatformStaffController::class, 'suspend'])->name('staff.suspend');
            Route::patch('staff/{user}/activate',        [\App\Http\Controllers\SuperAdmin\PlatformStaffController::class, 'activate'])->name('staff.activate');
            Route::patch('staff/{user}/reset-password',  [\App\Http\Controllers\SuperAdmin\PlatformStaffController::class, 'resetPassword'])->name('staff.reset-password');
            Route::delete('staff/{user}',                [\App\Http\Controllers\SuperAdmin\PlatformStaffController::class, 'destroy'])->name('staff.destroy');

            // What the platform team did (who entered which school, what they changed, from where)
            Route::get('audit',                          [\App\Http\Controllers\SuperAdmin\PlatformAuditController::class, 'index'])->name('audit.index');
            Route::get('audit/export',                   [\App\Http\Controllers\SuperAdmin\PlatformAuditController::class, 'export'])->name('audit.export');

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

// Online fee payments: providers tell the school about payments here (checked, then confirmed with the provider directly),
// and send parents back here after paying, sometimes as a form post from their own site.
Route::post('/webhooks/payments/{provider}/{school}', PaymentWebhookController::class)->whereAlpha('provider')->whereNumber('school')->middleware('throttle:120,1')->name('webhooks.payments');
Route::match(['get', 'post'], '/payments/return/{reference}', [ParentPaymentController::class, 'returned'])->where('reference', '[A-Za-z0-9]{8,60}')->middleware('throttle:30,1')
    // No session here: a form post from the provider's site carries no login, and starting a fresh session would sign the parent out
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class, \Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class, \App\Http\Middleware\HandleInertiaRequests::class])
    ->name('payments.return');

// Entrance exams on the computer: applicants log in with the exam number and PIN on their slip (no account)
Route::prefix('exam')->name('exam.')->controller(CandidateCbtController::class)->group(function () {
    Route::get('/', 'login')->name('login');
    Route::post('login', 'signIn')->middleware('throttle:10,1')->name('sign-in');
    Route::post('logout', 'signOut')->name('sign-out');
    Route::get('start', 'show')->name('show');
    Route::post('start', 'start')->middleware('throttle:20,1')->name('start');
    Route::get('paper', 'exam')->name('paper');
    Route::post('answer', 'answer')->middleware('throttle:240,1')->name('answer');
    Route::post('flag', 'flag')->middleware('throttle:240,1')->name('flag');
    Route::post('left', 'left')->middleware('throttle:60,1')->name('left');
    Route::post('ping', 'ping')->middleware('throttle:60,1')->name('ping');
    Route::post('submit', 'submit')->name('submit');
    Route::get('done', 'done')->name('done');
    Route::get('images/{question}', 'image')->whereNumber('question')->name('image');
});

// Guides for schools and developers: open to everyone, signed in or not
Route::get('/docs', [\App\Http\Controllers\DocsController::class, 'index'])->middleware('throttle:120,1')->name('docs.index');
Route::get('/docs/{slug}', [\App\Http\Controllers\DocsController::class, 'show'])->where('slug', '[a-z0-9-]+')->middleware('throttle:120,1')->name('docs.show');
// Public admission form (no auth)
// Anyone can check that a testimonial or transfer certificate is genuine, using the code printed on it
// What a student ID card's QR opens: staff of the school go to the scan screen, anyone else sees if the card is valid
Route::get('/id/{token}', IdCardCheckController::class)->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:60,1')->name('id-cards.check');
Route::get('/verify/{code}', [CertificateVerifyController::class, 'show'])->where('code', '[A-Za-z0-9]{6,16}')->middleware('throttle:30,1')->name('certificates.verify');
// A school's public website (open to everyone, only when the school switched it on)
Route::get('/s/{slug}/feed.xml', [PublicSiteController::class, 'feed'])->where('slug', '[a-z0-9-]+')->middleware('throttle:60,1')->name('site.feed');
Route::get('/s/{slug}/news', [PublicSiteController::class, 'news'])->where('slug', '[a-z0-9-]+')->middleware('throttle:120,1')->name('site.news');
Route::get('/s/{slug}/news/{post}', [PublicSiteController::class, 'post'])->where('slug', '[a-z0-9-]+')->where('post', '[a-z0-9-]+')->middleware('throttle:120,1')->name('site.post');
Route::get('/s/{slug}/gallery', [PublicSiteController::class, 'gallery'])->where('slug', '[a-z0-9-]+')->middleware('throttle:120,1')->name('site.gallery');
Route::get('/s/{slug}/gallery/{album}', [PublicSiteController::class, 'album'])->where('slug', '[a-z0-9-]+')->where('album', '[a-z0-9-]+')->middleware('throttle:120,1')->name('site.album');
Route::get('/s/{slug}/site.json', [PublicSiteController::class, 'siteJson'])->where('slug', '[a-z0-9-]+')->middleware('throttle:60,1')->name('site.json');
Route::get('/s/{slug}/news.json', [PublicSiteController::class, 'newsJson'])->where('slug', '[a-z0-9-]+')->middleware('throttle:60,1')->name('site.news-json');
Route::get('/s/{slug}/sitemap.xml', [PublicSiteController::class, 'sitemap'])->where('slug', '[a-z0-9-]+')->middleware('throttle:60,1')->name('site.sitemap');
Route::get('/s/{slug}/sign-in', [PublicSiteController::class, 'signIn'])->where('slug', '[a-z0-9-]+')->middleware('throttle:120,1')->name('site.sign-in');
Route::post('/s/{slug}/contact', [PublicSiteController::class, 'contact'])->where('slug', '[a-z0-9-]+')->middleware('throttle:6,1')->name('site.contact');
Route::get('/s/{slug}/form/{form}', [\App\Http\Controllers\SiteFormPublicController::class, 'show'])->where('slug', '[a-z0-9-]+')->whereNumber('form')->middleware('throttle:60,1')->name('site.form.show');
Route::post('/s/{slug}/form/{form}', [\App\Http\Controllers\SiteFormPublicController::class, 'submit'])->where('slug', '[a-z0-9-]+')->whereNumber('form')->middleware('throttle:20,1')->name('site.form.submit');
Route::post('/s/{slug}/newsletter', [NewsletterPublicController::class, 'subscribe'])->where('slug', '[a-z0-9-]+')->middleware('throttle:20,1')->name('site.newsletter.subscribe');
Route::get('/s/{slug}/newsletter/confirm/{token}', [NewsletterPublicController::class, 'confirmPage'])->where('slug', '[a-z0-9-]+')->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:30,1')->name('site.newsletter.confirm');
Route::post('/s/{slug}/newsletter/confirm/{token}', [NewsletterPublicController::class, 'confirm'])->where('slug', '[a-z0-9-]+')->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:30,1')->name('site.newsletter.confirm.do');
Route::get('/s/{slug}/newsletter/unsubscribe/{token}', [NewsletterPublicController::class, 'unsubscribePage'])->where('slug', '[a-z0-9-]+')->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:30,1')->name('site.newsletter.unsubscribe');
Route::post('/s/{slug}/newsletter/unsubscribe/{token}', [NewsletterPublicController::class, 'unsubscribe'])->where('slug', '[a-z0-9-]+')->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:30,1')->name('site.newsletter.unsubscribe.do');
Route::get('/s/{slug}/search', [PublicSiteController::class, 'search'])->where('slug', '[a-z0-9-]+')->middleware('throttle:60,1')->name('site.search');
Route::get('/s/{slug}/{page?}', [PublicSiteController::class, 'show'])->where('slug', '[a-z0-9-]+')->where('page', '[a-z0-9_-]+')->middleware('throttle:120,1')->name('site.show');
// Read-only data for a school's own scripts, with the key from Website > Developer
Route::middleware(['school.token', 'throttle:60,1'])->prefix('api/school')->group(function () {
    Route::get('me', [\App\Http\Controllers\Api\SchoolApiController::class, 'me']);
    Route::get('inquiries', [\App\Http\Controllers\Api\SchoolApiController::class, 'inquiries']);
});
Route::get('/apply/{school}',  [PublicAdmissionController::class, 'show'])->name('public.admission.show');
Route::post('/apply/{school}', [PublicAdmissionController::class, 'submit'])->middleware('throttle:10,1')->name('public.admission.submit');
Route::get('/apply/{school}/status',  [PublicAdmissionController::class, 'status'])->middleware('throttle:30,1')->name('public.admission.status');
Route::post('/apply/{school}/status/accept', [PublicAdmissionController::class, 'accept'])->middleware('throttle:10,1')->name('public.admission.accept');
