<?php

use App\Http\Controllers\App\AccountantController;
use App\Http\Controllers\App\AccountantProfileController;
use App\Http\Controllers\App\AttendanceController;
use App\Http\Controllers\App\BroadcastMessageController;
use App\Http\Controllers\App\CanteenController;
use App\Http\Controllers\App\ClassFeeController;
use App\Http\Controllers\App\DashboardController as AppDashboardController;
use App\Http\Controllers\App\EndOfYearController;
use App\Http\Controllers\App\EnrollmentController;
use App\Http\Controllers\App\ExtraAttendanceController;
use App\Http\Controllers\App\ExtraController;
use App\Http\Controllers\App\ExtraMenuController;
use App\Http\Controllers\App\ExtraRefundController;
use App\Http\Controllers\App\ExtraStockController;
use App\Http\Controllers\App\ExtraTransportController;
use App\Http\Controllers\App\FeeController;
use App\Http\Controllers\App\FinancialReportController;
use App\Http\Controllers\App\GouterController;
use App\Http\Controllers\App\MessageController;
use App\Http\Controllers\App\NotificationLogController;
use App\Http\Controllers\App\ParentController;
use App\Http\Controllers\App\PaymentController;
use App\Http\Controllers\App\ReportCardController;
use App\Http\Controllers\App\SchoolClassController;
use App\Http\Controllers\App\SchoolYearController;
use App\Http\Controllers\App\SmsSettingsController;
use App\Http\Controllers\App\StudentController;
use App\Http\Controllers\App\StudentInstallmentController;
use App\Http\Controllers\App\SubjectController;
use App\Http\Controllers\App\TeacherAssignmentController;
use App\Http\Controllers\App\TeacherController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolOnboardingController;
use App\Http\Controllers\SuperAdmin\ActivityLogController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\SchoolController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\SubscriptionPlanController;
use App\Http\Controllers\SuperAdmin\SystemSettingController;
use App\Http\Controllers\SuperAdmin\UserController;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\GradeController;
use App\Models\Message;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Page d'accueil - Toujours afficher la page de connexion
// Route::get('/', function () {
//     return view('auth.login');
// });

// ═══════════════════════════════════════════════════════════════
// ROUTES PUBLIQUES - VALIDATION DU CONTRAT ÉCOLE
// ═══════════════════════════════════════════════════════════════
Route::get('/ecole/valider-contrat/{token}', [SchoolOnboardingController::class, 'showContract'])
    ->name('school.validate-contract');

Route::post('/ecole/valider-contrat/{token}', [SchoolOnboardingController::class, 'validateContract'])
    ->name('school.validate-contract.submit');

Route::middleware(['auth', 'role:super_admin'])->prefix('superadmin')->name('superadmin.')->group(function () {

    Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('/schools', SchoolController::class);
    Route::resource('/users', UserController::class);
    Route::resource('/plans', SubscriptionPlanController::class)->except(['show']);

    // ✅ AJOUTEZ CETTE LIGNE POUR LES JOURNAUX D'ACTIVITÉ
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    // Paramètres système
    Route::get('/settings', [SystemSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SystemSettingController::class, 'update'])->name('settings.update');

    // ROUTES D'ABONNEMENT ET DE RENOUVELLEMENT
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::get('/subscriptions/{id}/renew', [SubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::post('/subscriptions/{id}/renew', [SubscriptionController::class, 'storeRenewal'])->name('subscriptions.store-renewal');

    Route::get('/subscriptions/pending', [SubscriptionController::class, 'pendingRequests'])->name('subscriptions.pending');
    Route::post('/subscriptions/requests/{subRequest}/approve', [SubscriptionController::class, 'approveRequest'])->name('subscriptions.requests.approve');
    Route::post('/subscriptions/requests/{subRequest}/reject', [SubscriptionController::class, 'rejectRequest'])->name('subscriptions.requests.reject');
});

// --------------------------------------------------------------------------
// Routes Admin École
// --------------------------------------------------------------------------

Route::middleware(['auth', 'school.active', 'role:school_admin,teacher,parent,accountant', 'tenant'])->prefix('app')->name('app.')->group(function () {

    // ==========================================================================
    // Ouvert au personnel comptable (rôle "accountant") : uniquement inscriptions
    // et paiements, plus les échéances/sélecteurs dont ces écrans dépendent.
    // ==========================================================================

    // Route AJAX pour récupérer les élèves par classe (utilisée par le formulaire de paiement)
    Route::get('/students/by-class', [PaymentController::class, 'getStudentsByClass'])
        ->name('students.by-class');

    // Inscriptions
    Route::get('/enrollments/export', [EnrollmentController::class, 'export'])->name('enrollments.export');
    Route::resource('/enrollments', EnrollmentController::class);

    // Paiements
    Route::get('/payments/export', [PaymentController::class, 'export'])->name('payments.export');
    Route::resource('/payments', PaymentController::class)->except(['edit', 'update']);
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');

    // Échéances (créées/supprimées depuis les écrans d'inscription et de paiement)
    Route::resource('/installments', StudentInstallmentController::class)
        ->only(['store', 'destroy'])
        ->names([
            'store' => 'installments.store',
            'destroy' => 'installments.destroy',
        ]);

    // Profil personnel du comptable (infos + mot de passe uniquement, pas les infos école :
    // ça reste dans ProfileController, réservé plus bas à school_admin/teacher/parent).
    Route::get('/accountant-profile', [AccountantProfileController::class, 'edit'])
        ->name('accountant-profile.edit');
    Route::put('/accountant-profile', [AccountantProfileController::class, 'update'])
        ->name('accountant-profile.update');
    Route::put('/accountant-profile/password', [AccountantProfileController::class, 'updatePassword'])
        ->name('accountant-profile.password.update');

    // ==========================================================================
    // Tout le reste : réservé à school_admin, teacher, parent (le personnel
    // comptable n'y a PAS accès, conformément à son rôle restreint).
    // ==========================================================================
    Route::middleware('role:school_admin,teacher,parent')->group(function () {

        Route::get('/dashboard', [AppDashboardController::class, 'index'])->name('dashboard');

        // Années scolaires
        Route::resource('/school-years', SchoolYearController::class);

        // Frais
        Route::resource('/fees', FeeController::class)
            ->middleware('role:school_admin');

        Route::resource('/students', StudentController::class);
        Route::get('/students/by-matricule/{matricule}', [StudentController::class, 'getByMatricule'])
            ->name('students.by-matricule');
        Route::get('/students/{student}/dossier', [StudentController::class, 'dossier'])
            ->name('students.dossier');

        // ==========================================
        // EXPORTS ADMIN (Élèves)
        // ==========================================
        Route::get('/students/export/excel', [StudentController::class, 'exportExcel'])
            ->name('students.export.excel'); // Laravel ajoutera automatiquement "app." devant -> app.students.export.excel

        Route::get('/students/export/pdf', [StudentController::class, 'exportPdf'])
            ->name('students.export.pdf'); // -> app.students.export.pdf

        // Parents (annuaire des comptes parents, avec indication des enfants liés).
        // Réservé à l'admin école : ce sont les coordonnées d'autres familles.
        Route::get('/parents', [ParentController::class, 'index'])
            ->name('parents.index')->middleware('role:school_admin');
        Route::get('/parents/{parentUser}', [ParentController::class, 'show'])
            ->name('parents.show')->middleware('role:school_admin');

        // Personnel comptable (gestion des comptes) : réservé à l'admin école.
        Route::resource('/accountants', AccountantController::class)
            ->except(['show'])
            ->middleware('role:school_admin');

        // Classes
        Route::resource('/classes', SchoolClassController::class);

        // 📨 MESSAGERIE ADMIN ÉCOLE
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');

        // ✅ IMPORTANT : Les routes spécifiques (sans paramètre dynamique) DOIVENT être placées AVANT les routes avec {message}
        Route::get('/messages/broadcast', [BroadcastMessageController::class, 'create'])->name('messages.broadcast');
        Route::post('/messages/broadcast', [BroadcastMessageController::class, 'store'])->name('messages.broadcast.store');

        // Les routes avec paramètres dynamiques {message} viennent ENSUITE
        Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/{message}/reply', [MessageController::class, 'reply'])->name('messages.reply');

        // Routes pour les sms
        Route::get('/settings/sms', [SmsSettingsController::class, 'index'])->name('settings.sms');
        Route::post('/settings/sms', [SmsSettingsController::class, 'update'])->name('settings.sms.update');

        // Présences
        Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
        Route::get('/attendances/create', [AttendanceController::class, 'create'])->name('attendances.create');
        Route::post('/attendances', [AttendanceController::class, 'store'])->name('attendances.store');
        Route::get('/attendances/by-date/{date}', [AttendanceController::class, 'showByDate'])->name('attendances.show-by-date');

        // Notes et Bulletins
        Route::get('/report-cards/bulk-download', [ReportCardController::class, 'downloadClassBulk'])
            ->name('report-cards.bulk-download');
        Route::resource('/report-cards', ReportCardController::class);
        Route::get('/report-cards/{reportCard}/pdf', [ReportCardController::class, 'downloadPdf'])->name('report-cards.pdf');

        // Matières
        Route::resource('/subjects', SubjectController::class)->only(['index', 'create', 'store', 'destroy']);

        // Personnel Enseignant (Assignation aux classes)
        Route::resource('/teacher-assignments', TeacherAssignmentController::class)
            ->only(['index', 'create', 'store', 'destroy']);

        // Gestion des Enseignants (CRUD)
        Route::resource('/teachers', TeacherController::class)->except(['show']);

        // Configuration des frais par classe
        Route::get('/class-fees', [ClassFeeController::class, 'index'])->name('class-fees.index');
        Route::get('/class-fees/{schoolClass}/edit', [ClassFeeController::class, 'edit'])->name('class-fees.edit');
        Route::put('/class-fees/{schoolClass}', [ClassFeeController::class, 'update'])->name('class-fees.update');

        // ==========================================
        // ÉTATS FINANCIERS
        // ==========================================
        Route::get('/financial/unpaid-by-class', [FinancialReportController::class, 'unpaidByClass'])
            ->name('financial.unpaid_by_class');
        Route::get('/financial/class-detail/{classId}', [FinancialReportController::class, 'classDetail'])
            ->name('financial.class_detail');

        // ==========================================
        // EXPORTS ÉTATS FINANCIERS
        // ==========================================
        Route::get('/financial/export/unpaid-by-class/excel', [FinancialReportController::class, 'exportUnpaidByClassExcel'])
            ->name('financial.export.unpaid_by_class.excel');
        Route::get('/financial/export/unpaid-by-class/pdf', [FinancialReportController::class, 'exportUnpaidByClassPdf'])
            ->name('financial.export.unpaid_by_class.pdf');
        Route::get('/financial/export/class-detail/{classId}/excel', [FinancialReportController::class, 'exportClassDetailExcel'])
            ->name('financial.export.class_detail.excel');
        Route::get('/financial/export/class-detail/{classId}/pdf', [FinancialReportController::class, 'exportClassDetailPdf'])
            ->name('financial.export.class_detail.pdf');

        // Détail financier d'un élève
        Route::get('/financial/student-detail/{studentId}', [FinancialReportController::class, 'studentDetail'])
            ->name('financial.student_detail');

        // Exports du détail d'un élève
        Route::get('/financial/export/student-detail/{studentId}/excel', [FinancialReportController::class, 'exportStudentDetailExcel'])
            ->name('financial.export.student_detail.excel');
        Route::get('/financial/export/student-detail/{studentId}/pdf', [FinancialReportController::class, 'exportStudentDetailPdf'])
            ->name('financial.export.student_detail.pdf');

        Route::get('/notifications', [NotificationLogController::class, 'index'])
            ->name('notifications.index');

        Route::get('/end-of-year', [EndOfYearController::class, 'index'])->name('end-of-year.index');
        Route::get('/end-of-year/{class}', [EndOfYearController::class, 'show'])->name('end-of-year.show');
        Route::post('/end-of-year/student/{student}/decision', [EndOfYearController::class, 'updateDecision'])->name('end-of-year.update-decision');
        Route::post('/end-of-year/{class}/migrate', [EndOfYearController::class, 'migrateClass'])->name('end-of-year.migrate');

        // PROFIL ADMIN ÉCOLE (le changement de mot de passe est intégré au formulaire de profil)
        Route::get('/profile', [App\Http\Controllers\App\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [App\Http\Controllers\App\ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [App\Http\Controllers\App\ProfileController::class, 'updatePassword'])->name('profile.password.update');

    }); // fin du sous-groupe role:school_admin,teacher,parent

});

/*
|--------------------------------------------------------------------------
| ESPACE ENSEIGNANT (Mobile-First)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'teacher', 'school.active', 'tenant'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/classes', [DashboardController::class, 'classes'])->name('classes');
    Route::get('/classes/{id}', [DashboardController::class, 'classDetails'])->name('class.details');

    Route::get('/attendance', [App\Http\Controllers\Teacher\AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/{classId?}/create', [App\Http\Controllers\Teacher\AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [App\Http\Controllers\Teacher\AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/{classId?}/history', [App\Http\Controllers\Teacher\AttendanceController::class, 'history'])->name('attendance.history');
    // ==========================================
    // EXPORTS ENSEIGNANT (Présences)
    // ==========================================
    Route::get('/attendance/export/excel', [App\Http\Controllers\Teacher\AttendanceController::class, 'exportAttendanceExcel'])
        ->name('attendance.export.excel');
    Route::get('/attendance/export/pdf', [App\Http\Controllers\Teacher\AttendanceController::class, 'exportAttendancePdf'])
        ->name('attendance.export.pdf');
    // Gestion des Notes et Évaluations
    Route::get('/classes/{classId}/grades', [GradeController::class, 'index'])->name('grades.index');
    Route::get('/classes/{classId}/grades/{subjectId}/create', [GradeController::class, 'create'])->name('grades.create');
    Route::post('/classes/{classId}/grades', [GradeController::class, 'store'])->name('grades.store');
    // Page de sélection de la classe pour les notes (accessible depuis le sidebar)
    Route::get('/grades', [GradeController::class, 'selectClass'])->name('grades.select');
    // ==========================================
    // ✅ PROFIL ENSEIGNANT (Ces 3 lignes sont OBLIGATOIRES)
    // ==========================================
    Route::get('/profile', [App\Http\Controllers\Teacher\ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [App\Http\Controllers\Teacher\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [App\Http\Controllers\Teacher\ProfileController::class, 'updatePassword'])->name('profile.password');
});

/*
|--------------------------------------------------------------------------
| ESPACE PARENT (Mobile-First)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'parent', 'school.active', 'tenant'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/', [App\Http\Controllers\Parent\DashboardController::class, 'index'])->name('dashboard');
    // ==========================================
    // PROFIL PARENT (Informations personnelles)
    // ==========================================
    Route::get('/profile', [App\Http\Controllers\Parent\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\Parent\ProfileController::class, 'update'])->name('profile.update');

    // (Vos routes de mot de passe sont déjà là, c'est parfait)
    Route::get('/profile/password', [App\Http\Controllers\Parent\ProfileController::class, 'editPassword'])->name('profile.password');
    Route::post('/profile/password', [App\Http\Controllers\Parent\ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('/messages', [App\Http\Controllers\Parent\MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/create', [App\Http\Controllers\Parent\MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [App\Http\Controllers\Parent\MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{message}', [App\Http\Controllers\Parent\MessageController::class, 'show'])->name('messages.show');

    Route::get('/{student}', [App\Http\Controllers\Parent\DashboardController::class, 'childDetails'])->name('child.details');
    Route::get('/{student}/grades', [App\Http\Controllers\Parent\GradeController::class, 'index'])->name('grades.index');
    Route::get('/{student}/grades/{reportCard}/pdf', [App\Http\Controllers\Parent\GradeController::class, 'downloadPdf'])->name('grades.pdf');
    Route::get('/attendance/{studentId}', [App\Http\Controllers\Parent\AttendanceController::class, 'index'])
        ->name('attendance.index');
    Route::get('/{student}/payments', [App\Http\Controllers\Parent\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/{student}/payments/{payment}/receipt', [App\Http\Controllers\Parent\PaymentController::class, 'downloadReceipt'])->name('payments.receipt');

    // Extras (« Mes extras »)
    Route::get('/{student}/extras', [App\Http\Controllers\Parent\ExtraController::class, 'index'])->name('extras.index');
    Route::get('/{student}/extras/catalogue', [App\Http\Controllers\Parent\ExtraController::class, 'catalogue'])->name('extras.catalogue');
    Route::post('/{student}/extras/{extraId}/request', [App\Http\Controllers\Parent\ExtraController::class, 'request'])->name('extras.request');
    Route::post('/{student}/extras/{subscriptionId}/suspend', [App\Http\Controllers\Parent\ExtraController::class, 'suspend'])->name('extras.suspend');
    Route::get('/{student}/extras/{subscriptionId}/payments/{paymentId}/receipt', [App\Http\Controllers\Parent\ExtraController::class, 'downloadReceipt'])->name('extras.payments.receipt');

});

// ==========================================
// CANTINE SCOLAIRE
// ==========================================
Route::middleware(['auth', 'school.active', 'role:school_admin', 'tenant'])->prefix('canteen')->name('canteen.')->group(function () {
    // Tarifs
    Route::get('/rates', [CanteenController::class, 'ratesIndex'])->name('rates.index');
    Route::get('/rates/create', [CanteenController::class, 'ratesCreate'])->name('rates.create');
    Route::post('/rates', [CanteenController::class, 'ratesStore'])->name('rates.store');
    Route::get('/rates/{id}/edit', [CanteenController::class, 'ratesEdit'])->name('rates.edit');
    Route::put('/rates/{id}', [CanteenController::class, 'ratesUpdate'])->name('rates.update');
    Route::delete('/rates/{id}', [CanteenController::class, 'ratesDestroy'])->name('rates.destroy');

    // Routes AJAX pour le formulaire d'inscription dynamique
    Route::get('/classes-by-cycle', [CanteenController::class, 'getClassesByCycle'])->name('classes-by-cycle');
    Route::get('/students-by-class', [CanteenController::class, 'getStudentsByClass'])->name('students-by-class');
    Route::get('/subscriptions-by-class', [CanteenController::class, 'getSubscriptionsByClass'])->name('subscriptions-by-class');
    // Inscriptions des élèves
    Route::get('/subscriptions', [CanteenController::class, 'subscriptionsIndex'])->name('subscriptions.index');
    Route::post('/subscriptions', [CanteenController::class, 'subscriptionsStore'])->name('subscriptions.store');
    Route::delete('/subscriptions/{id}', [CanteenController::class, 'subscriptionsDestroy'])->name('subscriptions.destroy');

    // Paiements
    Route::get('/payments', [CanteenController::class, 'paymentsIndex'])->name('payments.index');
    Route::post('/payments', [CanteenController::class, 'paymentsStore'])->name('payments.store');

    // Rapports cantine
    Route::get('/reports/unpaid-by-class', [CanteenController::class, 'unpaidByClass'])->name('reports.unpaid_by_class');
    Route::get('/reports/class-detail/{classId}', [CanteenController::class, 'classDetail'])->name('reports.class_detail');
    Route::get('/reports/student-detail/{studentId}', [CanteenController::class, 'studentDetail'])->name('reports.student_detail');

});

// ==========================================
// GOÛTER MATERNELLE
// ==========================================
Route::middleware(['auth', 'school.active', 'role:school_admin', 'tenant'])->prefix('gouter')->name('gouter.')->group(function () {
    // Tarifs
    Route::get('/rates', [GouterController::class, 'ratesIndex'])->name('rates.index');
    Route::get('/rates/create', [GouterController::class, 'ratesCreate'])->name('rates.create');
    Route::post('/rates', [GouterController::class, 'ratesStore'])->name('rates.store');
    Route::get('/rates/{id}/edit', [GouterController::class, 'ratesEdit'])->name('rates.edit');
    Route::put('/rates/{id}', [GouterController::class, 'ratesUpdate'])->name('rates.update');
    Route::delete('/rates/{id}', [GouterController::class, 'ratesDestroy'])->name('rates.destroy');

    // Routes AJAX pour le formulaire d'inscription dynamique
    Route::get('/maternelle-classes', [GouterController::class, 'getMaternelleClasses'])->name('maternelle-classes');
    Route::get('/students-by-class', [GouterController::class, 'getStudentsByClass'])->name('students-by-class');
    Route::get('/subscriptions-by-class', [GouterController::class, 'getSubscriptionsByClass'])->name('subscriptions-by-class');

    // Inscriptions des élèves
    Route::get('/subscriptions', [GouterController::class, 'subscriptionsIndex'])->name('subscriptions.index');
    Route::post('/subscriptions', [GouterController::class, 'subscriptionsStore'])->name('subscriptions.store');
    Route::delete('/subscriptions/{id}', [GouterController::class, 'subscriptionsDestroy'])->name('subscriptions.destroy');

    // Paiements
    Route::get('/payments', [GouterController::class, 'paymentsIndex'])->name('payments.index');
    Route::post('/payments', [GouterController::class, 'paymentsStore'])->name('payments.store');
    Route::get('/payments/{payment}/receipt', [GouterController::class, 'receipt'])->name('payments.receipt');

    // Rapports
    Route::get('/reports/unpaid-by-class', [GouterController::class, 'unpaidByClass'])->name('reports.unpaid_by_class');
});

// ==========================================
// EXTRAS (Services & prestations scolaires : transport, garderie, activités, sorties, ...)
// ==========================================
Route::middleware(['auth', 'school.active', 'role:school_admin', 'tenant'])->prefix('extras')->name('extras.')->group(function () {
    // Catégories
    Route::get('/categories', [ExtraController::class, 'categoriesIndex'])->name('categories.index');
    Route::post('/categories', [ExtraController::class, 'categoriesStore'])->name('categories.store');
    Route::put('/categories/{id}', [ExtraController::class, 'categoriesUpdate'])->name('categories.update');
    Route::delete('/categories/{id}', [ExtraController::class, 'categoriesDestroy'])->name('categories.destroy');

    // Catalogue
    Route::get('/catalogue', [ExtraController::class, 'catalogueIndex'])->name('catalogue.index');
    Route::get('/catalogue/create', [ExtraController::class, 'catalogueCreate'])->name('catalogue.create');
    Route::post('/catalogue', [ExtraController::class, 'catalogueStore'])->name('catalogue.store');
    Route::get('/catalogue/{id}/edit', [ExtraController::class, 'catalogueEdit'])->name('catalogue.edit');
    Route::put('/catalogue/{id}', [ExtraController::class, 'catalogueUpdate'])->name('catalogue.update');
    Route::delete('/catalogue/{id}', [ExtraController::class, 'catalogueDestroy'])->name('catalogue.destroy');

    // Tarifs (sous-formulaire de la fiche extra)
    Route::post('/catalogue/{extraId}/tarifs', [ExtraController::class, 'tarifsStore'])->name('tarifs.store');
    Route::put('/tarifs/{id}', [ExtraController::class, 'tarifsUpdate'])->name('tarifs.update');
    Route::delete('/tarifs/{id}', [ExtraController::class, 'tarifsDestroy'])->name('tarifs.destroy');

    // Planning (sous-formulaire de la fiche extra)
    Route::post('/catalogue/{extraId}/schedules', [ExtraController::class, 'schedulesStore'])->name('schedules.store');
    Route::delete('/schedules/{id}', [ExtraController::class, 'schedulesDestroy'])->name('schedules.destroy');

    // AJAX
    Route::get('/classes-by-cycle', [ExtraController::class, 'classesByCycle'])->name('classes-by-cycle');
    Route::get('/students-by-class', [ExtraController::class, 'studentsByClass'])->name('students-by-class');
    Route::get('/tarif-for-class', [ExtraController::class, 'tarifForClass'])->name('tarif-for-class');

    // Inscriptions
    Route::get('/subscriptions', [ExtraController::class, 'subscriptionsIndex'])->name('subscriptions.index');
    Route::get('/subscriptions/create', [ExtraController::class, 'subscriptionsCreate'])->name('subscriptions.create');
    Route::post('/subscriptions', [ExtraController::class, 'subscriptionsStore'])->name('subscriptions.store');
    Route::delete('/subscriptions/{id}', [ExtraController::class, 'subscriptionsDestroy'])->name('subscriptions.destroy');
    Route::patch('/subscriptions/{id}/validate', [ExtraController::class, 'subscriptionsValidate'])->name('subscriptions.validate');
    Route::patch('/subscriptions/{id}/promote', [ExtraController::class, 'subscriptionsPromote'])->name('subscriptions.promote');
    Route::patch('/subscriptions/{id}/toggle-authorization', [ExtraController::class, 'subscriptionsToggleAuthorization'])->name('subscriptions.toggle-authorization');
    Route::get('/subscriptions/{id}/authorization-pdf', [ExtraController::class, 'subscriptionsAuthorizationPdf'])->name('subscriptions.authorization-pdf');
    Route::get('/subscriptions/pdf', [ExtraController::class, 'subscriptionsPdf'])->name('subscriptions.pdf');

    // Paiements
    Route::get('/payments', [ExtraController::class, 'paymentsIndex'])->name('payments.index');
    Route::post('/payments', [ExtraController::class, 'paymentsStore'])->name('payments.store');
    Route::get('/payments/{payment}/receipt', [ExtraController::class, 'paymentsReceipt'])->name('payments.receipt');

    // Remboursements
    Route::get('/refunds', [ExtraRefundController::class, 'index'])->name('refunds.index');
    Route::post('/refunds', [ExtraRefundController::class, 'store'])->name('refunds.store');
    Route::get('/refunds/{subscriptionId}/suggested', [ExtraRefundController::class, 'suggested'])->name('refunds.suggested');

    // Stocks (uniformes, fournitures, kits scolaires)
    Route::get('/stocks', [ExtraStockController::class, 'index'])->name('stocks.index');
    Route::post('/stocks', [ExtraStockController::class, 'itemsStore'])->name('stocks.store');
    Route::put('/stocks/{id}', [ExtraStockController::class, 'itemsUpdate'])->name('stocks.update');
    Route::delete('/stocks/{id}', [ExtraStockController::class, 'itemsDestroy'])->name('stocks.destroy');
    Route::post('/stocks/movements', [ExtraStockController::class, 'movementsStore'])->name('stocks.movements.store');

    // Rapports
    Route::get('/reports/unpaid', [ExtraController::class, 'reportsUnpaid'])->name('reports.unpaid');
    Route::get('/reports/unpaid/pdf', [ExtraController::class, 'reportsUnpaidPdf'])->name('reports.unpaid.pdf');
    Route::get('/dashboard', [ExtraController::class, 'dashboard'])->name('dashboard');

    // Présences / consommations + QR de pointage
    Route::get('/attendances', [ExtraAttendanceController::class, 'index'])->name('attendances.index');
    Route::post('/attendances', [ExtraAttendanceController::class, 'store'])->name('attendances.store');
    Route::get('/attendances/scan', [ExtraAttendanceController::class, 'scanForm'])->name('attendances.scan');
    Route::post('/attendances/scan', [ExtraAttendanceController::class, 'scanStore'])->name('attendances.scan.store');
    Route::get('/subscriptions/{id}/qrcode', [ExtraAttendanceController::class, 'qrcode'])->name('subscriptions.qrcode');
    Route::post('/attendances/{attendanceId}/bill-overage', [ExtraAttendanceController::class, 'billOverage'])->name('attendances.bill-overage');

    // Menus (cantine)
    Route::get('/menus', [ExtraMenuController::class, 'index'])->name('menus.index');
    Route::post('/menus', [ExtraMenuController::class, 'store'])->name('menus.store');
    Route::delete('/menus/{id}', [ExtraMenuController::class, 'destroy'])->name('menus.destroy');

    // Transport
    Route::prefix('transport')->name('transport.')->group(function () {
        Route::get('/vehicles', [ExtraTransportController::class, 'vehiclesIndex'])->name('vehicles.index');
        Route::post('/vehicles', [ExtraTransportController::class, 'vehiclesStore'])->name('vehicles.store');
        Route::put('/vehicles/{id}', [ExtraTransportController::class, 'vehiclesUpdate'])->name('vehicles.update');
        Route::delete('/vehicles/{id}', [ExtraTransportController::class, 'vehiclesDestroy'])->name('vehicles.destroy');

        Route::get('/routes', [ExtraTransportController::class, 'routesIndex'])->name('routes.index');
        Route::post('/routes', [ExtraTransportController::class, 'routesStore'])->name('routes.store');
        Route::delete('/routes/{id}', [ExtraTransportController::class, 'routesDestroy'])->name('routes.destroy');
        Route::post('/routes/{routeId}/stops', [ExtraTransportController::class, 'stopsStore'])->name('stops.store');
        Route::delete('/stops/{id}', [ExtraTransportController::class, 'stopsDestroy'])->name('stops.destroy');

        Route::get('/assignments', [ExtraTransportController::class, 'assignmentsIndex'])->name('assignments.index');
        Route::post('/assignments', [ExtraTransportController::class, 'assignmentsStore'])->name('assignments.store');
        Route::delete('/assignments/{id}', [ExtraTransportController::class, 'assignmentsDestroy'])->name('assignments.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Routes de profil et paramètres (Global pour TOUS les utilisateurs authentifiés)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Profil (Breeze par défaut)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Paramètres (Déplacé ici pour éviter le préfixe 'app.' qui causait l'erreur)
    Route::get('/profile/settings', [ProfileController::class, 'settings'])->name('profile.settings');
    Route::patch('/profile/settings', [ProfileController::class, 'updateSettings'])->name('profile.settings.update');
});

Route::get('/logout-form', function () {
    return view('logout-form');
});

// Route publique pour la demande de création d'école (accessible depuis la démo ou la landing page)
Route::get('/demande-compte', [SchoolOnboardingController::class, 'showRequestForm'])
    ->name('request-account');

Route::post('/demande-compte', [SchoolOnboardingController::class, 'storeRequest'])
    ->name('request-account.store');

// Route pour la connexion en un clic à la démo
Route::get('/demo-login', [DemoController::class, 'login'])->name('demo.login');

Route::get('/', function () {
    $plans = SubscriptionPlan::active()->orderBy('sort_order')->orderBy('monthly_price')->get();

    return view('landing', compact('plans'));
})->name('landing');

/*
|--------------------------------------------------------------------------
| Routes d'authentification (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
