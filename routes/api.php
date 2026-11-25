<?php

use App\Http\Controllers\Api\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true, 'service' => 'secondlook']));

Route::get('/jobs', [PortalController::class, 'jobs']);
Route::get('/jobs/{job}', [PortalController::class, 'job']);
Route::post('/applications', [PortalController::class, 'apply']);

Route::get('/portal/{token}', [PortalController::class, 'portal']);
Route::get('/portal/{token}/assessment', [PortalController::class, 'assessmentQuestions']);
Route::post('/portal/{token}/assessment', [PortalController::class, 'submitAssessment']);

Route::get('/help', [PortalController::class, 'help']);
