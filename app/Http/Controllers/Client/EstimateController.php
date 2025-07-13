<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EstimateController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.or.client');
    }
    
    /**
     * Скачать файл сметы
     *
     * @param  \App\Models\Estimate  $estimate
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(Estimate $estimate)
    {
        // Проверяем, что смета принадлежит проекту клиента
        $project = Project::findOrFail($estimate->project_id);
        
        // Администратор имеет доступ ко всем сметам
        $user = User::find(auth()->id());
        if (!$user->isAdmin() && $project->phone !== $user->phone) {
            abort(403, 'У вас нет доступа к этой смете');
        }
        
        // Используем EstimateJsonExportService для генерации файла
        try {
            // Генерируем Excel файл для клиента напрямую через сервис
            $jsonExportService = app(\App\Services\EstimateJsonExportService::class);
            return $jsonExportService->exportToExcel($estimate, 'client');
        } catch (\Exception $e) {
            Log::error('Ошибка при скачивании сметы клиентом: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            abort(500, 'Ошибка при генерации файла сметы: ' . $e->getMessage());
        }
    }
    
    /**
     * Скачать PDF файл сметы
     *
     * @param  \App\Models\Estimate  $estimate
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadPdf(Estimate $estimate)
    {
        // Проверяем, что смета принадлежит проекту клиента
        $project = Project::findOrFail($estimate->project_id);
        
        // Администратор имеет доступ ко всем сметам
        $user = User::find(auth()->id());
        if (!$user->isAdmin() && $project->phone !== $user->phone) {
            abort(403, 'У вас нет доступа к этой смете');
        }
        
        // Используем EstimateJsonExportService для генерации PDF файла
        try {
            // Генерируем PDF файл для клиента
            $jsonExportService = app(\App\Services\EstimateJsonExportService::class);
            return $jsonExportService->exportToPdf($estimate, 'client');
        } catch (\Exception $e) {
            Log::error('Ошибка при скачивании PDF сметы клиентом: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            abort(500, 'Ошибка при генерации PDF файла сметы: ' . $e->getMessage());
        }
    }
}
