<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentShareController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', [DocumentController::class, 'index'])->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::resource('documents', DocumentController::class)
        ->only(['store', 'show', 'update', 'destroy']);
    Route::post('documents/{document}/shares', [DocumentShareController::class, 'store'])->name('documents.shares.store');
    Route::delete('documents/{document}/shares/{user}', [DocumentShareController::class, 'destroy'])->name('documents.shares.destroy');

    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
