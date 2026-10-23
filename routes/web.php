<?php

use App\Http\Controllers\AcademicController;
use App\Http\Controllers\Auth\AccessMethodsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\NotesController;
use App\Http\Middleware\AcademicConfigured;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function (): void {
    Route::view('/registro', 'auth.register')->name('register');
    Route::post('/registro', [RegisteredUserController::class, 'store'])->middleware('throttle:registration');
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login-ip');
    Route::get('/olvide-mi-contrasena', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/olvide-mi-contrasena', [PasswordResetController::class, 'email'])->middleware('throttle:password-email')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/restablecer-contrasena', [PasswordResetController::class, 'update'])->middleware('throttle:password-update')->name('password.update');
});
Route::middleware('auth')->group(function (): void {
    Route::get('/verificar-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/verificar-email/reenviar', [EmailVerificationController::class, 'send'])->middleware('throttle:verification-ip')->name('verification.send');
    Route::get('/verificar-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('throttle:verification-ip')->name('verification.verify');
    Route::get('/dashboard', Dashboard::class)->middleware(['verified', AcademicConfigured::class])->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['guest', 'throttle:oauth'])->group(function (): void {
    Route::get('/auth/{provider}/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
    Route::get('/auth/completar-email', [OAuthController::class, 'email'])->name('oauth.email');
    Route::post('/auth/completar-email', [OAuthController::class, 'storeEmail'])->middleware('throttle:registration')->name('oauth.email.store');
});
Route::get('/auth/{provider}/callback', [OAuthController::class, 'callback'])->middleware('throttle:oauth')->name('oauth.callback');
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/configuracion-academica', [AcademicController::class, 'choose'])->name('academic.choose');
    Route::post('/configuracion-academica', [AcademicController::class, 'select'])->name('academic.select');
    Route::get('/configuracion-academica/materias', [AcademicController::class, 'draft'])->name('academic.draft');
    Route::post('/configuracion-academica/materias', [AcademicController::class, 'saveDraft'])->name('academic.save');
    Route::get('/configuracion-academica/resumen', [AcademicController::class, 'summary'])->name('academic.summary');
    Route::post('/configuracion-academica/confirmar', [AcademicController::class, 'finish'])->name('academic.finish');
    Route::post('/configuracion-academica/cancelar', [AcademicController::class, 'cancel'])->name('academic.cancel');
    Route::middleware(AcademicConfigured::class)->group(function (): void {
        Route::get('/mi-carrera', [AcademicController::class, 'career'])->name('career.index');
        Route::get('/mi-carrera/{course}', [AcademicController::class, 'detail'])->whereNumber('course')->name('career.detail');
        Route::post('/mi-carrera/{course}', [AcademicController::class, 'update'])->whereNumber('course')->name('career.update');
    });
    Route::get('/metodos-de-acceso', [AccessMethodsController::class, 'index'])->name('access-methods.index');
    Route::post('/metodos-de-acceso/reautenticar', [AccessMethodsController::class, 'confirmPassword'])->middleware('throttle:reauth')->name('reauth.password');
    Route::post('/metodos-de-acceso/{provider}/vincular', [OAuthController::class, 'link'])->middleware('throttle:oauth')->name('oauth.link');
    Route::post('/metodos-de-acceso/{provider}/reautenticar', [OAuthController::class, 'reauthenticate'])->middleware('throttle:reauth')->name('oauth.reauthenticate');
});

Route::middleware(['auth', 'verified', AcademicConfigured::class])->group(function (): void {
    Route::get('/calendario', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendario/nuevo', [CalendarController::class, 'create'])->name('calendar.create');
    Route::post('/calendario', [CalendarController::class, 'store'])->name('calendar.store');
    Route::get('/calendario/{event}', [CalendarController::class, 'show'])->whereNumber('event')->name('calendar.show');
    Route::get('/calendario/{event}/editar', [CalendarController::class, 'edit'])->whereNumber('event')->name('calendar.edit');
    Route::post('/calendario/{event}', [CalendarController::class, 'update'])->whereNumber('event')->name('calendar.update');
    Route::get('/calendario/{event}/eliminar', [CalendarController::class, 'confirm'])->whereNumber('event')->name('calendar.delete');
    Route::post('/calendario/{event}/eliminar', [CalendarController::class, 'destroy'])->whereNumber('event')->name('calendar.destroy');
    Route::get('/notas', [NotesController::class, 'index'])->name('notes.index');
    Route::get('/notas/nueva', [NotesController::class, 'create'])->name('notes.create');
    Route::post('/notas', [NotesController::class, 'store'])->name('notes.store');
    Route::get('/notas/{note}', [NotesController::class, 'show'])->whereNumber('note')->name('notes.show');
    Route::get('/notas/{note}/editar', [NotesController::class, 'edit'])->whereNumber('note')->name('notes.edit');
    Route::post('/notas/{note}', [NotesController::class, 'update'])->whereNumber('note')->name('notes.update');
    Route::get('/notas/{note}/eliminar', [NotesController::class, 'confirm'])->whereNumber('note')->name('notes.delete');
    Route::post('/notas/{note}/eliminar', [NotesController::class, 'destroy'])->whereNumber('note')->name('notes.destroy');
    Route::get('/notificaciones', [InboxController::class, 'index'])->name('inbox.index');
    Route::post('/notificaciones/leidas', [InboxController::class, 'readAll'])->name('inbox.read-all');
    Route::post('/notificaciones/{notice}/leida', [InboxController::class, 'read'])->whereNumber('notice')->name('inbox.read');
    Route::get('/notificaciones/{notice}', [InboxController::class, 'open'])->whereNumber('notice')->name('inbox.open');
    Route::get('/cartelera', [InboxController::class, 'board'])->name('board.index');
    Route::get('/cartelera/{post}', [InboxController::class, 'post'])->whereNumber('post')->name('board.show');
});
