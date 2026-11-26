<?php

use App\Http\Controllers\Api\CandidateAuthController;
use App\Http\Controllers\Api\PortalController;
use App\Http\Controllers\Api\PracticalTaskController;
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

// Competency Assessment: vacancy-specific practical tasks scored against a rubric, validated by a recruiter
Route::get('/portal/{token}/tasks', [PracticalTaskController::class, 'index']);
Route::get('/portal/{token}/tasks/{slug}', [PracticalTaskController::class, 'show']);
Route::post('/portal/{token}/tasks/{slug}', [PracticalTaskController::class, 'submit']);

// Candidate accounts (rate-limited)
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/auth/register', [CandidateAuthController::class, 'register']);
    Route::post('/auth/login', [CandidateAuthController::class, 'login']);
    Route::post('/portal/{token}/password', [CandidateAuthController::class, 'setPassword']);
});
Route::get('/auth/me/{token}', [CandidateAuthController::class, 'me']);

// Local only: load the spec's demo story (fresh graduate + informal-sector rediscovery) on top of existing data
if (app()->environment('local')) {
    Route::post('/dev/demo-story', function () {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoStorySeeder', '--force' => true]);

        return response()->json(['ok' => true, 'output' => trim(\Illuminate\Support\Facades\Artisan::output())]);
    });
    Route::post('/dev/migrate', function () {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

        return response()->json(['ok' => true, 'output' => trim(\Illuminate\Support\Facades\Artisan::output())]);
    });
}
