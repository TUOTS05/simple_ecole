<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\App\DashboardController as AppDashboardController;
use Illuminate\Support\Facades\Route;
use App\Models\Message;
use App\Http\Controllers\SuperAdmin\SubscriptionPlanController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\ActivityLogController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\SuperAdmin\SystemSettingController;



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
Route::get('/ecole/valider-contrat/{token}', [\App\Http\Controllers\SchoolOnboardingController::class, 'showContract'])
    ->name('school.validate-contract');

Route::post('/ecole/valider-contrat/{token}', [\App\Http\Controllers\SchoolOnboardingController::class, 'validateContract'])
    ->name('school.validate-contract.submit');


Route::middleware(['auth', 'role:super_admin'])->prefix('superadmin')->name('superadmin.')->group(function () {

    Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('/schools', \App\Http\Controllers\SuperAdmin\SchoolController::class);
    Route::resource('/users', \App\Http\Controllers\SuperAdmin\UserController::class);
    Route::resource('/plans', \App\Http\Controllers\SuperAdmin\SubscriptionPlanController::class);

    // ✅ AJOUTEZ CETTE LIGNE POUR LES JOURNAUX D'ACTIVITÉ
    Route::get('/activity-logs', [\App\Http\Controllers\SuperAdmin\ActivityLogController::class, 'index'])->name('activity-logs.index');

    // Paramètres système
    Route::get('/settings', [\App\Http\Controllers\SuperAdmin\SystemSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [\App\Http\Controllers\SuperAdmin\SystemSettingController::class, 'update'])->name('settings.update');

    // ROUTES D'ABONNEMENT ET DE RENOUVELLEMENT
    Route::get('/subscriptions', [\App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions', [\App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::get('/subscriptions/{id}/renew', [\App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::post('/subscriptions/{id}/renew', [\App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'storeRenewal'])->name('subscriptions.store-renewal');

    Route::get('/subscriptions/pending', [\App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'pendingRequests'])->name('subscriptions.pending');
    Route::post('/subscriptions/requests/{subRequest}/approve', [\App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'approveRequest'])->name('subscriptions.requests.approve');
    Route::post('/subscriptions/requests/{subRequest}/reject', [\App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'rejectRequest'])->name('subscriptions.requests.reject');
});

#--------------------------------------------------------------------------
# Routes Admin École
#--------------------------------------------------------------------------

Route::middleware(['auth', 'school.active', 'role:school_admin,teacher,parent', 'tenant'])->prefix('app')->name('app.')->group(function () {
    Route::get('/dashboard', [AppDashboardController::class, 'index'])->name('dashboard');

    // Années scolaires
    Route::resource('/school-years', \App\Http\Controllers\App\SchoolYearController::class);

    // Frais
    Route::resource('/fees', \App\Http\Controllers\App\FeeController::class)
        ->middleware('role:school_admin');

    // Route AJAX pour récupérer les élèves par classe
    Route::get('/students/by-class', [\App\Http\Controllers\App\PaymentController::class, 'getStudentsByClass'])
        ->name('students.by-class');

    // Élèves
    Route::get('/enrollments/export', [\App\Http\Controllers\App\EnrollmentController::class, 'export'])->name('enrollments.export');
    
    Route::resource('/students', \App\Http\Controllers\App\StudentController::class);
    Route::get('/students/by-matricule/{matricule}', [\App\Http\Controllers\App\StudentController::class, 'getByMatricule'])
    ->name('students.by-matricule');
    Route::get('/students/{student}/dossier', [\App\Http\Controllers\App\StudentController::class, 'dossier'])
    ->name('students.dossier');
    
        // ==========================================
    // EXPORTS ADMIN (Élèves)
    // ==========================================
    Route::get('/students/export/excel', [\App\Http\Controllers\App\StudentController::class, 'exportExcel'])
        ->name('students.export.excel'); // Laravel ajoutera automatiquement "app." devant -> app.students.export.excel
        
    Route::get('/students/export/pdf', [\App\Http\Controllers\App\StudentController::class, 'exportPdf'])
        ->name('students.export.pdf'); // -> app.students.export.pdf
    // Classes
    Route::resource('/classes', \App\Http\Controllers\App\SchoolClassController::class);

    // Inscriptions
    Route::resource('/enrollments', \App\Http\Controllers\App\EnrollmentController::class);

        // 📨 MESSAGERIE ADMIN ÉCOLE
    Route::get('/messages', [\App\Http\Controllers\App\MessageController::class, 'index'])->name('messages.index');
    
    // ✅ IMPORTANT : Les routes spécifiques (sans paramètre dynamique) DOIVENT être placées AVANT les routes avec {message}
    Route::get('/messages/broadcast', [\App\Http\Controllers\App\BroadcastMessageController::class, 'create'])->name('messages.broadcast');
    Route::post('/messages/broadcast', [\App\Http\Controllers\App\BroadcastMessageController::class, 'store'])->name('messages.broadcast.store');

    // Les routes avec paramètres dynamiques {message} viennent ENSUITE
    Route::get('/messages/{message}', [\App\Http\Controllers\App\MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{message}/reply', [\App\Http\Controllers\App\MessageController::class, 'reply'])->name('messages.reply');

    // Paiements
    Route::get('/payments/export', [\App\Http\Controllers\App\PaymentController::class, 'export'])->name('payments.export');
    Route::resource('/payments', \App\Http\Controllers\App\PaymentController::class);
    Route::get('/payments/{payment}/receipt', [\App\Http\Controllers\App\PaymentController::class, 'receipt'])->name('payments.receipt');

    // Routes pour les sms
    Route::get('/settings/sms', [App\Http\Controllers\App\SmsSettingsController::class, 'index'])->name('settings.sms');
    Route::post('/settings/sms', [App\Http\Controllers\App\SmsSettingsController::class, 'update'])->name('settings.sms.update');

    // Présences
    Route::get('/attendances', [\App\Http\Controllers\App\AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/attendances/create', [\App\Http\Controllers\App\AttendanceController::class, 'create'])->name('attendances.create');
    Route::post('/attendances', [\App\Http\Controllers\App\AttendanceController::class, 'store'])->name('attendances.store');
    Route::get('/attendances/by-date/{date}', [\App\Http\Controllers\App\AttendanceController::class, 'showByDate'])->name('attendances.show-by-date');

    // Notes et Bulletins
    Route::get('/report-cards/bulk-download', [App\Http\Controllers\App\ReportCardController::class, 'downloadClassBulk'])
        ->name('report-cards.bulk-download');
    Route::resource('/report-cards', \App\Http\Controllers\App\ReportCardController::class);
    Route::get('/report-cards/{reportCard}/pdf', [\App\Http\Controllers\App\ReportCardController::class, 'downloadPdf'])->name('report-cards.pdf');

    // Matières
    Route::resource('/subjects', \App\Http\Controllers\App\SubjectController::class)->only(['index', 'create', 'store', 'destroy']);

    // Personnel Enseignant (Assignation aux classes)
    Route::resource('/teacher-assignments', \App\Http\Controllers\App\TeacherAssignmentController::class)
        ->only(['index', 'create', 'store', 'destroy']);

    // Gestion des Enseignants (CRUD)
    Route::resource('/teachers', \App\Http\Controllers\App\TeacherController::class);

    // Configuration des frais par classe
    Route::get('/class-fees', [\App\Http\Controllers\App\ClassFeeController::class, 'index'])->name('class-fees.index');
    Route::get('/class-fees/{schoolClass}/edit', [\App\Http\Controllers\App\ClassFeeController::class, 'edit'])->name('class-fees.edit');
    Route::put('/class-fees/{schoolClass}', [\App\Http\Controllers\App\ClassFeeController::class, 'update'])->name('class-fees.update');

    // Échéances
    Route::resource('/installments', \App\Http\Controllers\App\StudentInstallmentController::class)
        ->only(['store', 'destroy'])
        ->names([
            'store' => 'installments.store',
            'destroy' => 'installments.destroy'
        ]);
    
    // ==========================================
    // ÉTATS FINANCIERS
    // ==========================================
    Route::get('/financial/unpaid-by-class', [\App\Http\Controllers\App\FinancialReportController::class, 'unpaidByClass'])
        ->name('financial.unpaid_by_class');
    Route::get('/financial/class-detail/{classId}', [\App\Http\Controllers\App\FinancialReportController::class, 'classDetail'])
        ->name('financial.class_detail');

    // ==========================================
    // EXPORTS ÉTATS FINANCIERS
    // ==========================================
    Route::get('/financial/export/unpaid-by-class/excel', [\App\Http\Controllers\App\FinancialReportController::class, 'exportUnpaidByClassExcel'])
        ->name('financial.export.unpaid_by_class.excel');
    Route::get('/financial/export/unpaid-by-class/pdf', [\App\Http\Controllers\App\FinancialReportController::class, 'exportUnpaidByClassPdf'])
        ->name('financial.export.unpaid_by_class.pdf');
    Route::get('/financial/export/class-detail/{classId}/excel', [\App\Http\Controllers\App\FinancialReportController::class, 'exportClassDetailExcel'])
        ->name('financial.export.class_detail.excel');
    Route::get('/financial/export/class-detail/{classId}/pdf', [\App\Http\Controllers\App\FinancialReportController::class, 'exportClassDetailPdf'])
        ->name('financial.export.class_detail.pdf');

    // Détail financier d'un élève
    Route::get('/financial/student-detail/{studentId}', [\App\Http\Controllers\App\FinancialReportController::class, 'studentDetail'])
        ->name('financial.student_detail');

    // Exports du détail d'un élève
    Route::get('/financial/export/student-detail/{studentId}/excel', [\App\Http\Controllers\App\FinancialReportController::class, 'exportStudentDetailExcel'])
        ->name('financial.export.student_detail.excel');
    Route::get('/financial/export/student-detail/{studentId}/pdf', [\App\Http\Controllers\App\FinancialReportController::class, 'exportStudentDetailPdf'])
        ->name('financial.export.student_detail.pdf');

    Route::get('/notifications', [App\Http\Controllers\App\NotificationLogController::class, 'index'])
    ->name('notifications.index');

    Route::get('/end-of-year', [\App\Http\Controllers\App\EndOfYearController::class, 'index'])->name('end-of-year.index');
    Route::get('/end-of-year/{class}', [\App\Http\Controllers\App\EndOfYearController::class, 'show'])->name('end-of-year.show');
    Route::post('/end-of-year/student/{student}/decision', [\App\Http\Controllers\App\EndOfYearController::class, 'updateDecision'])->name('end-of-year.update-decision');
    Route::post('/end-of-year/{class}/migrate', [\App\Http\Controllers\App\EndOfYearController::class, 'migrateClass'])->name('end-of-year.migrate');

          // PROFIL ADMIN ÉCOLE (le changement de mot de passe est intégré au formulaire de profil)
    Route::get('/profile', [\App\Http\Controllers\App\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\App\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\App\ProfileController::class, 'updatePassword'])->name('profile.password.update');

});

/*
|--------------------------------------------------------------------------
| ESPACE ENSEIGNANT (Mobile-First)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'teacher', 'school.active', 'tenant'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Teacher\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/classes', [\App\Http\Controllers\Teacher\DashboardController::class, 'classes'])->name('classes');
    Route::get('/classes/{id}', [\App\Http\Controllers\Teacher\DashboardController::class, 'classDetails'])->name('class.details');

    Route::get('/attendance', [\App\Http\Controllers\Teacher\AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/{classId?}/create', [\App\Http\Controllers\Teacher\AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [\App\Http\Controllers\Teacher\AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/{classId?}/history', [\App\Http\Controllers\Teacher\AttendanceController::class, 'history'])->name('attendance.history');
    // ==========================================
    // EXPORTS ENSEIGNANT (Présences)
    // ==========================================
    Route::get('/attendance/export/excel', [\App\Http\Controllers\Teacher\AttendanceController::class, 'exportAttendanceExcel'])
        ->name('attendance.export.excel');
    Route::get('/attendance/export/pdf', [\App\Http\Controllers\Teacher\AttendanceController::class, 'exportAttendancePdf'])
        ->name('attendance.export.pdf');
    // Gestion des Notes et Évaluations
    Route::get('/classes/{classId}/grades', [\App\Http\Controllers\Teacher\GradeController::class, 'index'])->name('grades.index');
    Route::get('/classes/{classId}/grades/{subjectId}/create', [\App\Http\Controllers\Teacher\GradeController::class, 'create'])->name('grades.create');
    Route::post('/classes/{classId}/grades', [\App\Http\Controllers\Teacher\GradeController::class, 'store'])->name('grades.store');
    // Page de sélection de la classe pour les notes (accessible depuis le sidebar)
    Route::get('/grades', [\App\Http\Controllers\Teacher\GradeController::class, 'selectClass'])->name('grades.select');
     // ==========================================
    // ✅ PROFIL ENSEIGNANT (Ces 3 lignes sont OBLIGATOIRES)
    // ==========================================
    Route::get('/profile', [\App\Http\Controllers\Teacher\ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [\App\Http\Controllers\Teacher\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\Teacher\ProfileController::class, 'updatePassword'])->name('profile.password');
});

/*
|--------------------------------------------------------------------------
| ESPACE PARENT (Mobile-First)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'parent', 'school.active', 'tenant'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Parent\DashboardController::class, 'index'])->name('dashboard');
        // ==========================================
    // PROFIL PARENT (Informations personnelles)
    // ==========================================
    Route::get('/profile', [\App\Http\Controllers\Parent\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\Parent\ProfileController::class, 'update'])->name('profile.update');

    // (Vos routes de mot de passe sont déjà là, c'est parfait)
    Route::get('/profile/password', [App\Http\Controllers\Parent\ProfileController::class, 'editPassword'])->name('profile.password');
    Route::post('/profile/password', [App\Http\Controllers\Parent\ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('/messages', [\App\Http\Controllers\Parent\MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/create', [\App\Http\Controllers\Parent\MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [\App\Http\Controllers\Parent\MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{message}', [\App\Http\Controllers\Parent\MessageController::class, 'show'])->name('messages.show');

    Route::get('/{student}', [\App\Http\Controllers\Parent\DashboardController::class, 'childDetails'])->name('child.details');
    Route::get('/{student}/grades', [\App\Http\Controllers\Parent\GradeController::class, 'index'])->name('grades.index');
    Route::get('/{student}/grades/{reportCard}/pdf', [\App\Http\Controllers\Parent\GradeController::class, 'downloadPdf'])->name('grades.pdf');
    Route::get('/attendance/{studentId}', [\App\Http\Controllers\Parent\AttendanceController::class, 'index'])
    ->name('attendance.index');
    Route::get('/{student}/attendance', [\App\Http\Controllers\Parent\AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/{student}/payments', [\App\Http\Controllers\Parent\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/{student}/payments/{payment}/receipt', [\App\Http\Controllers\Parent\PaymentController::class, 'downloadReceipt'])->name('payments.receipt');
    
});


// ==========================================
// CANTINE SCOLAIRE
// ==========================================
Route::middleware(['auth', 'school.active', 'role:school_admin', 'tenant'])->prefix('canteen')->name('canteen.')->group(function () {
    // Tarifs
    Route::get('/rates', [\App\Http\Controllers\App\CanteenController::class, 'ratesIndex'])->name('rates.index');
    Route::get('/rates/create', [\App\Http\Controllers\App\CanteenController::class, 'ratesCreate'])->name('rates.create');
    Route::post('/rates', [\App\Http\Controllers\App\CanteenController::class, 'ratesStore'])->name('rates.store');
    Route::get('/rates/{id}/edit', [\App\Http\Controllers\App\CanteenController::class, 'ratesEdit'])->name('rates.edit');
    Route::put('/rates/{id}', [\App\Http\Controllers\App\CanteenController::class, 'ratesUpdate'])->name('rates.update');
    Route::delete('/rates/{id}', [\App\Http\Controllers\App\CanteenController::class, 'ratesDestroy'])->name('rates.destroy');

    // Routes AJAX pour le formulaire d'inscription dynamique
    Route::get('/classes-by-cycle', [\App\Http\Controllers\App\CanteenController::class, 'getClassesByCycle'])->name('classes-by-cycle');
    Route::get('/students-by-class', [\App\Http\Controllers\App\CanteenController::class, 'getStudentsByClass'])->name('students-by-class');
    Route::get('/subscriptions-by-class', [\App\Http\Controllers\App\CanteenController::class, 'getSubscriptionsByClass'])->name('subscriptions-by-class');
    // Inscriptions des élèves
    Route::get('/subscriptions', [\App\Http\Controllers\App\CanteenController::class, 'subscriptionsIndex'])->name('subscriptions.index');
    Route::post('/subscriptions', [\App\Http\Controllers\App\CanteenController::class, 'subscriptionsStore'])->name('subscriptions.store');
    Route::delete('/subscriptions/{id}', [\App\Http\Controllers\App\CanteenController::class, 'subscriptionsDestroy'])->name('subscriptions.destroy');

    // Paiements
    Route::get('/payments', [\App\Http\Controllers\App\CanteenController::class, 'paymentsIndex'])->name('payments.index');
    Route::post('/payments', [\App\Http\Controllers\App\CanteenController::class, 'paymentsStore'])->name('payments.store');

    // Rapports cantine
    Route::get('/reports/unpaid-by-class', [\App\Http\Controllers\App\CanteenController::class, 'unpaidByClass'])->name('reports.unpaid_by_class');
    Route::get('/reports/class-detail/{classId}', [\App\Http\Controllers\App\CanteenController::class, 'classDetail'])->name('reports.class_detail');
    Route::get('/reports/student-detail/{studentId}', [\App\Http\Controllers\App\CanteenController::class, 'studentDetail'])->name('reports.student_detail');

});

// ==========================================
// GOÛTER MATERNELLE
// ==========================================
Route::middleware(['auth', 'school.active', 'role:school_admin', 'tenant'])->prefix('gouter')->name('gouter.')->group(function () {
    // Tarifs
    Route::get('/rates', [\App\Http\Controllers\App\GouterController::class, 'ratesIndex'])->name('rates.index');
    Route::get('/rates/create', [\App\Http\Controllers\App\GouterController::class, 'ratesCreate'])->name('rates.create');
    Route::post('/rates', [\App\Http\Controllers\App\GouterController::class, 'ratesStore'])->name('rates.store');
    Route::get('/rates/{id}/edit', [\App\Http\Controllers\App\GouterController::class, 'ratesEdit'])->name('rates.edit');
    Route::put('/rates/{id}', [\App\Http\Controllers\App\GouterController::class, 'ratesUpdate'])->name('rates.update');
    Route::delete('/rates/{id}', [\App\Http\Controllers\App\GouterController::class, 'ratesDestroy'])->name('rates.destroy');

    // Routes AJAX pour le formulaire d'inscription dynamique
    Route::get('/maternelle-classes', [\App\Http\Controllers\App\GouterController::class, 'getMaternelleClasses'])->name('maternelle-classes');
    Route::get('/students-by-class', [\App\Http\Controllers\App\GouterController::class, 'getStudentsByClass'])->name('students-by-class');
    Route::get('/subscriptions-by-class', [\App\Http\Controllers\App\GouterController::class, 'getSubscriptionsByClass'])->name('subscriptions-by-class');

    // Inscriptions des élèves
    Route::get('/subscriptions', [\App\Http\Controllers\App\GouterController::class, 'subscriptionsIndex'])->name('subscriptions.index');
    Route::post('/subscriptions', [\App\Http\Controllers\App\GouterController::class, 'subscriptionsStore'])->name('subscriptions.store');
    Route::delete('/subscriptions/{id}', [\App\Http\Controllers\App\GouterController::class, 'subscriptionsDestroy'])->name('subscriptions.destroy');

    // Paiements
    Route::get('/payments', [\App\Http\Controllers\App\GouterController::class, 'paymentsIndex'])->name('payments.index');
    Route::post('/payments', [\App\Http\Controllers\App\GouterController::class, 'paymentsStore'])->name('payments.store');
    Route::get('/payments/{payment}/receipt', [\App\Http\Controllers\App\GouterController::class, 'receipt'])->name('payments.receipt');

    // Rapports
    Route::get('/reports/unpaid-by-class', [\App\Http\Controllers\App\GouterController::class, 'unpaidByClass'])->name('reports.unpaid_by_class');
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
Route::get('/demande-compte', [App\Http\Controllers\SchoolOnboardingController::class, 'showRequestForm'])
    ->name('request-account');

Route::post('/demande-compte', [App\Http\Controllers\SchoolOnboardingController::class, 'storeRequest'])
    ->name('request-account.store');



// Route pour la connexion en un clic à la démo
Route::get('/demo-login', [DemoController::class, 'login'])->name('demo.login');

Route::get('/', function () {
    return view('landing');
})->name('landing');

/*
|--------------------------------------------------------------------------
| Routes d'authentification (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
