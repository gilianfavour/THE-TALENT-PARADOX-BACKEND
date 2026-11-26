<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PortalController;
use App\Http\Controllers\Api\PracticalTaskController;
use App\Http\Controllers\Api\TalentController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true, 'service' => 'secondlook']));

// Candidate authentication (token-based, password optional)
Route::prefix('/auth')->group(function () {
    Route::get('/me/{token}', [AuthController::class, 'me']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

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
    Route::post('/password', [AuthController::class, 'setPassword']);
});

// Competency Assessment: vacancy-specific practical tasks scored against a rubric, validated by a recruiter
Route::get('/portal/{token}/tasks', [PracticalTaskController::class, 'index']);
Route::get('/portal/{token}/tasks/{slug}', [PracticalTaskController::class, 'show']);
Route::post('/portal/{token}/tasks/{slug}', [PracticalTaskController::class, 'submit']);

// Local only: load the spec's demo story (fresh graduate + informal-sector rediscovery) on top of existing data
if (app()->environment('local')) {
    Route::post('/dev/demo-story', function () {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoStorySeeder', '--force' => true]);

        return response()->json(['ok' => true, 'output' => trim(\Illuminate\Support\Facades\Artisan::output())]);
    });
}
