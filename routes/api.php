<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CandidatureController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartementController;
use App\Http\Controllers\Api\ElectionController;
use App\Http\Controllers\Api\ProcesVerbalController;
use App\Http\Controllers\Api\ResultatController;
use App\Http\Controllers\Api\VoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/departement', [DepartementController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::apiResource('departements', DepartementController::class);
    Route::apiResource('elections', ElectionController::class);
    Route::apiResource('candidatures', CandidatureController::class);
    Route::get('owncandidatures', [CandidatureController::class, 'ownCandidature']);
    Route::get('/pendingcandidatures', [CandidatureController::class, 'pending']);

    Route::post('/elections/{election}/ouvrir', [ElectionController::class, 'ouvrir']);
    Route::post('/elections/{election}/fermer', [ElectionController::class, 'fermer']);
    Route::post('/candidatures/{candidature}/valider', [CandidatureController::class, 'valider']);
    Route::post('/candidatures/{candidature}/retirer', [CandidatureController::class, 'retirer']);
    Route::post('/votes', [VoteController::class, 'store']);
    Route::get('/votes/has-voted/{electionId}', [VoteController::class, 'hasVoted']);
    Route::get('/elections/{election}/resultats', [ResultatController::class, 'index']);
    Route::post('/elections/{election}/calculer-resultats', [ResultatController::class, 'calculer']);
    Route::post('/elections/{election}/generer-pv', [ProcesVerbalController::class, 'generer']);
    Route::get('/proces-verbaux', [ProcesVerbalController::class, 'index']);
    Route::get('/proces-verbaux/{procesVerbal}', [ProcesVerbalController::class, 'show']);
    Route::get('/proces-verbaux/{procesVerbal}/telecharger', [ProcesVerbalController::class, 'telecharger']);
});