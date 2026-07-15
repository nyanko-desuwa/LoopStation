<?php

use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventRegistrationController;
use App\Http\Controllers\Api\FacilityController;
use App\Http\Controllers\Api\HandoverController;
use App\Http\Controllers\Api\MeasurementUnitController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\WasteTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('api.auth.')->group(function (): void {
    Route::post('register', RegisterController::class)->name('register');
    Route::post('login', LoginController::class)->name('login');
    Route::post('forgot-password', [PasswordResetController::class, 'sendLink'])
        ->name('forgot-password');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])
        ->name('reset-password');
    Route::get('verify-email/{id}/{hash}', EmailVerificationController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verify-email');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', LogoutController::class)->name('logout');
        Route::get('me', MeController::class)->name('me');
        // Quyền của user đang đăng nhập (theo role → ROLE_PERMISSIONS).
        Route::get('me/permissions', [RolePermissionController::class, 'me'])
            ->name('me.permissions');
        Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });
});

// Facilities - list/show public (active only for guests/users);
// write actions require auth:sanctum + facility.* permission.
Route::get('facilities', [FacilityController::class, 'index'])->name('api.facilities.index');
Route::get('facilities/{facility}', [FacilityController::class, 'show'])->name('api.facilities.show');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('facilities', [FacilityController::class, 'store'])
        ->middleware('permission:facility.create')
        ->name('api.facilities.store');
    Route::put('facilities/{facility}', [FacilityController::class, 'update'])
        ->middleware('permission:facility.update')
        ->name('api.facilities.update');
    Route::patch('facilities/{facility}', [FacilityController::class, 'update'])
        ->middleware('permission:facility.update')
        ->name('api.facilities.patch');
    Route::delete('facilities/{facility}', [FacilityController::class, 'destroy'])
        ->middleware('permission:facility.delete')
        ->name('api.facilities.destroy');

    // RBAC catalog - manager only (permission.view / create / update / delete).
    Route::get('permissions', [PermissionController::class, 'index'])
        ->middleware('permission:permission.view')
        ->name('api.permissions.index');
    Route::get('permissions/{permission}', [PermissionController::class, 'show'])
        ->middleware('permission:permission.view')
        ->name('api.permissions.show');
    Route::post('permissions', [PermissionController::class, 'store'])
        ->middleware('permission:permission.create')
        ->name('api.permissions.store');
    Route::put('permissions/{permission}', [PermissionController::class, 'update'])
        ->middleware('permission:permission.update')
        ->name('api.permissions.update');
    Route::patch('permissions/{permission}', [PermissionController::class, 'update'])
        ->middleware('permission:permission.update')
        ->name('api.permissions.patch');
    Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])
        ->middleware('permission:permission.delete')
        ->name('api.permissions.destroy');

    // Role → permission mapping.
    Route::get('roles/{role}/permissions', [RolePermissionController::class, 'show'])
        ->middleware('permission:role_permission.view')
        ->name('api.roles.permissions.show');
    Route::put('roles/{role}/permissions', [RolePermissionController::class, 'sync'])
        ->middleware('permission:role_permission.update')
        ->name('api.roles.permissions.sync');

    // Measurement units - list/show public; write needs measurement_unit.*.
    Route::post('measurement-units', [MeasurementUnitController::class, 'store'])
        ->middleware('permission:measurement_unit.create')
        ->name('api.measurement-units.store');
    Route::put('measurement-units/{measurement_unit}', [MeasurementUnitController::class, 'update'])
        ->middleware('permission:measurement_unit.update')
        ->name('api.measurement-units.update');
    Route::patch('measurement-units/{measurement_unit}', [MeasurementUnitController::class, 'update'])
        ->middleware('permission:measurement_unit.update')
        ->name('api.measurement-units.patch');
    Route::delete('measurement-units/{measurement_unit}', [MeasurementUnitController::class, 'destroy'])
        ->middleware('permission:measurement_unit.delete')
        ->name('api.measurement-units.destroy');

    // Waste types - create: create OR create_custom; update/delete checked in request/controller.
    Route::post('waste-types', [WasteTypeController::class, 'store'])
        ->name('api.waste-types.store');
    Route::put('waste-types/{waste_type}', [WasteTypeController::class, 'update'])
        ->name('api.waste-types.update');
    Route::patch('waste-types/{waste_type}', [WasteTypeController::class, 'update'])
        ->name('api.waste-types.patch');
    Route::delete('waste-types/{waste_type}', [WasteTypeController::class, 'destroy'])
        ->name('api.waste-types.destroy');

    // Handover requests - đơn chuyển giao rác (đơn thường; event_id sau).
    Route::get('handovers', [HandoverController::class, 'index'])
        ->name('api.handovers.index');
    Route::post('handovers', [HandoverController::class, 'store'])
        ->middleware('permission:handover.create')
        ->name('api.handovers.store');
    Route::get('handovers/{handover}', [HandoverController::class, 'show'])
        ->name('api.handovers.show');
    Route::put('handovers/{handover}', [HandoverController::class, 'update'])
        ->name('api.handovers.update');
    Route::patch('handovers/{handover}', [HandoverController::class, 'update'])
        ->name('api.handovers.patch');
    Route::post('handovers/{handover}/cancel', [HandoverController::class, 'cancel'])
        ->name('api.handovers.cancel');
    Route::post('handovers/{handover}/approve', [HandoverController::class, 'approve'])
        ->middleware('permission:handover.approve')
        ->name('api.handovers.approve');
    Route::post('handovers/{handover}/reject', [HandoverController::class, 'reject'])
        ->middleware('permission:handover.reject')
        ->name('api.handovers.reject');
    Route::post('handovers/{handover}/assign-staff', [HandoverController::class, 'assignStaff'])
        ->middleware('permission:handover.assign_staff')
        ->name('api.handovers.assign-staff');
    Route::post('handovers/{handover}/reschedule', [HandoverController::class, 'reschedule'])
        ->name('api.handovers.reschedule');
    Route::post('handovers/{handover}/weight-logs', [HandoverController::class, 'recordWeight'])
        ->middleware('permission:handover.record_weight')
        ->name('api.handovers.weight-logs');
    Route::post('handovers/{handover}/complete', [HandoverController::class, 'complete'])
        ->middleware('permission:handover.complete')
        ->name('api.handovers.complete');

    // Events - write/manage behind permissions; list/show also public below.
    Route::post('events', [EventController::class, 'store'])
        ->middleware('permission:event.create')
        ->name('api.events.store');
    Route::put('events/{event}', [EventController::class, 'update'])
        ->middleware('permission:event.update')
        ->name('api.events.update');
    Route::patch('events/{event}', [EventController::class, 'update'])
        ->middleware('permission:event.update')
        ->name('api.events.patch');
    Route::delete('events/{event}', [EventController::class, 'destroy'])
        ->middleware('permission:event.delete')
        ->name('api.events.destroy');
    Route::post('events/{event}/activate', [EventController::class, 'activate'])
        ->middleware('permission:event.publish')
        ->name('api.events.activate');
    Route::post('events/{event}/end', [EventController::class, 'end'])
        ->middleware('permission:event.end')
        ->name('api.events.end');
    Route::post('events/{event}/cancel', [EventController::class, 'cancel'])
        ->middleware('permission:event.update')
        ->name('api.events.cancel');
    Route::post('events/{event}/staff', [EventController::class, 'assignStaff'])
        ->middleware('permission:event.assign_staff')
        ->name('api.events.staff.assign');
    Route::delete('events/{event}/staff/{staffId}', [EventController::class, 'unassignStaff'])
        ->middleware('permission:event.assign_staff')
        ->name('api.events.staff.unassign');
    Route::post('events/{event}/rewards', [EventController::class, 'storeReward'])
        ->middleware('permission:event.manage_rewards')
        ->name('api.events.rewards.store');
    Route::put('events/{event}/rewards/{reward}', [EventController::class, 'updateReward'])
        ->middleware('permission:event.manage_rewards')
        ->name('api.events.rewards.update');
    Route::delete('events/{event}/rewards/{reward}', [EventController::class, 'destroyReward'])
        ->middleware('permission:event.manage_rewards')
        ->name('api.events.rewards.destroy');

    // Event registrations.
    Route::get('events/{event}/registrations', [EventRegistrationController::class, 'index'])
        ->middleware('permission:event_registration.view')
        ->name('api.events.registrations.index');
    Route::post('events/{event}/registrations', [EventRegistrationController::class, 'store'])
        ->middleware('permission:event_registration.create')
        ->name('api.events.registrations.store');
    Route::delete('events/{event}/registrations/{registration}', [EventRegistrationController::class, 'destroy'])
        ->name('api.events.registrations.destroy');
    Route::post('events/check-in', [EventRegistrationController::class, 'checkInByQr'])
        ->name('api.events.check-in');
    Route::post('events/{event}/registrations/{registration}/check-in', [EventRegistrationController::class, 'staffCheckIn'])
        ->middleware('permission:event_registration.check_in')
        ->name('api.events.registrations.check-in');
    Route::post('events/{event}/registrations/{registration}/absent', [EventRegistrationController::class, 'markAbsent'])
        ->middleware('permission:event_registration.mark_absent')
        ->name('api.events.registrations.absent');
    Route::post('events/{event}/registrations/{registration}/unlock-minigame', [EventRegistrationController::class, 'unlockMinigame'])
        ->middleware('permission:event.unlock_minigame')
        ->name('api.events.registrations.unlock-minigame');
});

// Events public list/show (upcoming + active for guests).
Route::get('events', [EventController::class, 'index'])->name('api.events.index');
Route::get('events/{event}', [EventController::class, 'show'])->name('api.events.show');

// Measurement units - public catalog for handover forms.
Route::get('measurement-units', [MeasurementUnitController::class, 'index'])
    ->name('api.measurement-units.index');
Route::get('measurement-units/{measurement_unit}', [MeasurementUnitController::class, 'show'])
    ->name('api.measurement-units.show');

// Waste types - public list (system + own custom when authenticated).
Route::get('waste-types', [WasteTypeController::class, 'index'])
    ->name('api.waste-types.index');
Route::get('waste-types/{waste_type}', [WasteTypeController::class, 'show'])
    ->name('api.waste-types.show');
