<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// ->name('api.') : évite que les routes API écrasent les noms des routes web
// (tickets.show, tickets.update...), ce qui envoyait les liens et redirections vers /api/tickets/{id}.
Route::middleware(['auth:sanctum', 'active'])->name('api.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::patch('tickets/{ticket}/status', [TicketController::class, 'changeStatus']);
    Route::apiResource('tickets', TicketController::class);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
