<?php

use App\Http\Controllers\Api\PortalController;
use App\Http\Controllers\Api\TalentController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true, 'service' => 'secondlook']));

Route::get('/jobs', [PortalController::class, 'jobs']);
Route::get('/jobs/{job}', [PortalController::class, 'job']);
Route::post('/applications', [PortalController::class, 'apply']);

Route::get('/portal/{token}', [PortalController::class, 'portal']);
Route::get('/portal/{token}/assessment', [PortalController::class, 'assessmentQuestions']);
Route::post('/portal/{token}/assessment', [PortalController::class, 'submitAssessment']);

Route::get('/help', [PortalController::class, 'help']);

Route::get('/stats', [TalentController::class, 'stats']);

// Talent Discovery, Rediscovery and notifications (scoped to the private portal token)
Route::prefix('/portal/{token}')->group(function () {
    Route::get('/profile', [TalentController::class, 'profile']);
    Route::patch('/profile', [TalentController::class, 'updateProfile']);
    Route::post('/experiences', [TalentController::class, 'addExperience']);
    Route::delete('/experiences/{experience}', [TalentController::class, 'deleteExperience']);
    Route::get('/applications', [TalentController::class, 'applications']);
    Route::get('/opportunities', [TalentController::class, 'opportunities']);
    Route::post('/opportunities/{recommendation}/respond', [TalentController::class, 'respond']);
    Route::get('/notifications', [TalentController::class, 'notifications']);
    Route::post('/notifications/read', [TalentController::class, 'markRead']);
});
