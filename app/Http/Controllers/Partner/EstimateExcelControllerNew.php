<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Services\EstimateTemplateService;
use App\Services\MaterialsEstimateTemplateService;
use App\Services\EstimateJsonExportService;
use App\Http\Controllers\Partner\ExcelTemplateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Barryvdh\DomPDF\Facade\Pdf;

class EstimateExcelController extends Controller
{
    protected $estimateTemplateService;
    protected $materialsTemplateService;
    protected $jsonExportService;
    
    /**
     * Конструктор контроллера
     */
    public function __construct(
        EstimateTemplateService $estimateTemplateService,
        MaterialsEstimateTemplateService $materialsTemplateService,
        EstimateJsonExportService $jsonExportService
    ) {
        $this->estimateTemplateService = $estimateTemplateService;
        $this->materialsTemplateService = $materialsTemplateService;
        $this->jsonExportService = $jsonExportService;
    }

    /**
     * Экспортирует смету в файл Excel (новый метод, использующий JSON данные)
     */
    public function export(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        try {
            return $this->jsonExportService->exportToExcel($estimate);
        } catch (\Exception $e) {
            Log::error('Ошибка экспорта сметы в Excel: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Не удалось экспортировать смету: ' . $e->getMessage());
        }
    }

    /**
     * Экспортирует смету в файл PDF (новый метод, использующий JSON данные)
     */
    public function exportPdf(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        try {
            return $this->jsonExportService->exportToPdf($estimate);
        } catch (\Exception $e) {
            Log::error('Ошибка экспорта сметы в PDF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Не удалось экспортировать смету в PDF: ' . $e->getMessage());
        }
    }

    /**
     * Экспортирует смету в файл Excel для заказчика (новый метод)
     */
    public function exportClient(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        try {
            return $this->jsonExportService->exportToExcel($estimate, 'client');
        } catch (\Exception $e) {
            Log::error('Ошибка экспорта сметы для клиента: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Не удалось экспортировать смету для клиента: ' . $e->getMessage());
        }
    }

    /**
     * Экспортирует смету в файл Excel для мастера (новый метод)
     */
    public function exportContractor(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        try {
            return $this->jsonExportService->exportToExcel($estimate, 'contractor');
        } catch (\Exception $e) {
            Log::error('Ошибка экспорта сметы для мастера: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Не удалось экспортировать смету для мастера: ' . $e->getMessage());
        }
    }

    /**
     * Экспортирует смету в файл PDF для заказчика (новый метод)
     */
    public function exportPdfClient(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        try {
            return $this->jsonExportService->exportToPdf($estimate, 'client');
        } catch (\Exception $e) {
            Log::error('Ошибка экспорта PDF для клиента: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Не удалось экспортировать PDF для клиента: ' . $e->getMessage());
        }
    }

    /**
     * Экспортирует смету в файл PDF для мастера (новый метод)
     */
    public function exportPdfContractor(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        try {
            return $this->jsonExportService->exportToPdf($estimate, 'contractor');
        } catch (\Exception $e) {
            Log::error('Ошибка экспорта PDF для мастера: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Не удалось экспортировать PDF для мастера: ' . $e->getMessage());
        }
    }

    /**
     * Получение данных Excel для редактирования
     */
    public function getData(Estimate $estimate)
    {
        try {
            $this->authorize('view', $estimate);
            
            // Проверяем, есть ли файл
            if (!$estimate->file_path || !Storage::disk('public')->exists($estimate->file_path)) {
                // Если файла нет, создаем его
                $this->createInitialExcelFile($estimate);
            }
            
            // Загружаем файл и возвращаем структуру данных
            $filePath = storage_path('app/public/' . $estimate->file_path);
            $structure = $this->getExcelFileStructure($filePath, $estimate->type);
            
            return response()->json([
                'success' => true,
                'data' => $structure,
                'meta' => [
                    'file_path' => $estimate->file_path,
                    'estimate_id' => $estimate->id,
                    'estimate_type' => $estimate->type
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка получения данных Excel: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Не удалось загрузить данные: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Сохранение данных Excel
     */
    public function saveExcelData(Request $request, Estimate $estimate)
    {
        try {
            $this->authorize('update', $estimate);
            
            // Проверяем наличие данных
            if (!$request->has('excel_data')) {
                Log::warning('Запрос не содержит поля excel_data');
                return response()->json([
                    'success' => false,
                    'error' => 'Данные для сохранения не найдены'
                ], 400);
            }
            
            if (empty($request->excel_data)) {
                Log::warning('Поле excel_data пустое');
                return response()->json([
                    'success' => false,
                    'error' => 'Данные для сохранения пустые'
                ], 400);
            }
            
            Log::info('Получены Excel данные размером: ' . strlen($request->excel_data));
            
            // Декодируем base64
            $binaryData = base64_decode($request->excel_data);
            if ($binaryData === false) {
                Log::warning('Некорректное base64 кодирование данных');
                return response()->json([
                    'success' => false,
                    'error' => 'Некорректное кодирование данных'
                ], 400);
            }
            
            // Дополнительная проверка размера данных
            if (strlen($binaryData) < 1000) {
                Log::warning('Слишком маленький размер данных: ' . strlen($binaryData) . ' байт');
                return response()->json([
                    'success' => false,
                    'error' => 'Данные слишком малы для корректного Excel файла'
                ], 400);
            }
            
            // Проверяем магические байты Excel файла
            if (substr($binaryData, 0, 2) !== 'PK') {
                Log::warning('Данные не соответствуют формату XLSX (отсутствует сигнатура PK)');
                return response()->json([
                    'success' => false,
                    'error' => 'Некорректный формат данных Excel'
                ], 400);
            }
            
            // Генерируем путь для сохранения файла
            $fileName = 'estimate_' . $estimate->id . '_' . time() . '.xlsx';
            $filePath = 'estimates/' . $estimate->project_id . '/' . $fileName;
            
            // Создаем директорию если не существует
            $directory = dirname($filePath);
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            
            // Сохраняем файл
            Storage::disk('public')->put($filePath, $binaryData);
            
            // Обновляем запись в базе данных
            $estimate->update([
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => strlen($binaryData),
                'file_updated_at' => now()
            ]);
            
            // Пересчитываем сумму проекта
            $estimate->updateProjectAmounts();
            
            Log::info("Файл Excel сохранен для сметы #{$estimate->id}: {$filePath}");
            
            return response()->json([
                'success' => true,
                'message' => 'Данные успешно сохранены',
                'data' => [
                    'file_path' => $filePath,
                    'file_size' => strlen($binaryData),
                    'updated_at' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка при сохранении Excel-данных: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'error' => 'Произошла ошибка при сохранении: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Загрузка Excel файла
     */
    public function upload(Request $request, Estimate $estimate)
    {
        try {
            $this->authorize('update', $estimate);
            
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240'
            ]);
            
            $file = $request->file('file');
            
            // Генерируем уникальное имя файла
            $fileName = 'estimate_' . $estimate->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = 'estimates/' . $estimate->project_id . '/' . $fileName;
            
            // Сохраняем файл
            $path = $file->storeAs('estimates/' . $estimate->project_id, $fileName, 'public');
            
            // Обновляем запись в базе данных
            $estimate->update([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_updated_at' => now()
            ]);
            
            return redirect()->back()->with('success', 'Файл успешно загружен');
            
        } catch (\Exception $e) {
            Log::error('Ошибка при загрузке Excel файла: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ошибка при загрузке файла: ' . $e->getMessage());
        }
    }

    /**
     * Получение структуры Excel файла
     */
    protected function getExcelFileStructure($filePath, $estimateType)
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Определяем диапазон данных
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            // Получаем все данные
            $cellData = [];
            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $cell = $worksheet->getCell($col . $row);
                    $rowData[$col] = [
                        'value' => $cell->getCalculatedValue(),
                        'formula' => $cell->getValue(),
                        'dataType' => $cell->getDataType()
                    ];
                }
                $cellData[] = $rowData;
            }
            
            return [
                'rows' => $cellData,
                'highestRow' => $highestRow,
                'highestColumn' => $highestColumn,
                'sheetName' => $worksheet->getTitle()
            ];
            
        } catch (\Exception $e) {
            Log::error('Ошибка при определении структуры Excel-файла: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Создание начального Excel файла
     */
    public function createInitialExcelFile(Estimate $estimate)
    {
        try {
            // Используем соответствующий сервис для создания шаблона
            if ($estimate->type === 'materials') {
                $spreadsheet = $this->materialsTemplateService->createTemplateSpreadsheet($estimate);
            } else {
                $spreadsheet = $this->estimateTemplateService->createTemplateSpreadsheet($estimate);
            }
            
            // Генерируем путь для сохранения
            $fileName = 'estimate_' . $estimate->id . '_' . time() . '.xlsx';
            $filePath = 'estimates/' . $estimate->project_id . '/' . $fileName;
            
            // Создаем директорию если не существует
            $directory = dirname($filePath);
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            
            // Сохраняем файл
            $fullPath = storage_path('app/public/' . $filePath);
            $writer = new Xlsx($spreadsheet);
            $writer->save($fullPath);
            
            // Обновляем запись в базе данных
            $estimate->update([
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => filesize($fullPath),
                'file_updated_at' => now()
            ]);
            
            Log::info("Создан начальный Excel файл для сметы #{$estimate->id}: {$filePath}");
            
            return $filePath;
            
        } catch (\Exception $e) {
            Log::error("Ошибка создания Excel файла для сметы #{$estimate->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
