<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Partner\ProjectScheduleController;
use App\Http\Controllers\Partner\ExcelTemplateController;
use App\Http\Controllers\Partner\ProjectController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\Api\EstimateTemplatesController;
use App\Http\Controllers\Partner\EstimateController;

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

Route::middleware(['web', 'auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// API маршруты для работы с графиком проекта
Route::middleware(['web', 'auth'])->group(function () {
    // Поиск проектов для select'ов (используем web auth вместо sanctum)
    Route::get('/projects/search', [ProjectController::class, 'search']);
    
    // API маршруты для системы обучения (с дополнительной проверкой через middleware)
    Route::middleware('tours')->group(function () {
        Route::post('/tours/complete', [TourController::class, 'markCompleted']);
        Route::post('/tours/reset', [TourController::class, 'resetTours']);
    });
    
    // Получение данных разделов для создания смет
    Route::get('/excel-templates/sections-data', [ExcelTemplateController::class, 'getSectionsData']);
    
    // API для работы с шаблонами смет
    Route::get('/estimates/templates/{type}', [EstimateController::class, 'getTemplate'])
        ->name('api.estimates.templates')
        ->where('type', 'main|materials|additional');
    
    // Получение списка элементов графика с фильтрацией
    Route::get('/projects/{project}/schedule', [ProjectScheduleController::class, 'getItems']);
    
    // Работа с элементами графика
    Route::post('/projects/{project}/schedule/items', [ProjectScheduleController::class, 'storeItem']);
    Route::put('/projects/{project}/schedule/items/{item}', [ProjectScheduleController::class, 'updateItem']);
    Route::delete('/projects/{project}/schedule/items/{item}', [ProjectScheduleController::class, 'destroyItem']);
    
    // Обновление ссылки на график
    Route::put('/projects/{project}/schedule/link', [ProjectScheduleController::class, 'updateLink']);
    
    // Импорт из Excel
    Route::post('/projects/{project}/schedule/import', [ProjectScheduleController::class, 'importFromExcel']);
});

// Маршруты для API работы со сметами
Route::middleware('auth:sanctum')->group(function() {
    // Шаблоны для смет
    Route::get('estimates/templates', [EstimateTemplatesController::class, 'getSectionsData']);
    
    // Получение данных сметы в JSON формате
    Route::get('estimates/{estimate}/data', 'App\Http\Controllers\Partner\EstimateController@getData');
    Route::put('estimates/{estimate}', 'App\Http\Controllers\Partner\EstimateController@update');
    
    // Маршрут для загрузки шаблона сметы
    Route::get('templates/estimates/{type}', [EstimateController::class, 'getTemplateByType']);
});
