<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\EstimateClientPdfService;

class Estimate extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'type', // main, additional, materials
        'status', // draft, pending, approved
        'description',
        'json_data', // JSON данные сметы
        'user_id',
        'total_amount',
        'file_path', // Добавлено поле для хранения пути к файлу
        'file_updated_at',
        'file_size',
        'file_name',
    ];

    /**
     * Атрибуты, которые должны быть преобразованы.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'float',
        'file_updated_at' => 'datetime',
        'json_data' => 'array', // Автоматически кодируем/декодируем JSON
    ];

    /**
     * Получить проект, к которому принадлежит смета.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Получить пользователя, создавшего смету.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Получить элементы сметы.
     */
    public function items(): HasMany
    {
        return $this->hasMany(EstimateItem::class);
    }
    
    /**
     * Получить путь к файлу сметы
     *
     * @return string
     */
    public function getFilePathAttribute(): string
    {
        // Если путь к файлу сохранен в БД, используем его
        if (!empty($this->attributes['file_path'])) {
            return $this->attributes['file_path'];
        }
        
        // Иначе генерируем стандартный путь
        return "estimates/{$this->project_id}/{$this->id}.xlsx";
    }
    
    /**
     * Получить URL для скачивания файла сметы
     *
     * @return string|null
     */
    public function getFileUrlAttribute(): ?string
    {
        $path = $this->file_path;
        
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }
        
        return null;
    }
    
    /**
     * Обновить финансовые поля проекта на основе смет
     * Учитываются сметы со статусами "создана" (created), "черновик" (draft), "на согласовании" (pending) и "утверждена" (approved)
     * 
     * @return bool
     */
    public function updateProjectAmounts()
    {
        if (!$this->project_id) {
            \Illuminate\Support\Facades\Log::warning("updateProjectAmounts: отсутствует project_id");
            return false;
        }
        
        $project = $this->project;
        if (!$project) {
            \Illuminate\Support\Facades\Log::warning("updateProjectAmounts: не найден проект для project_id={$this->project_id}");
            return false;
        }
        
        // Для отладки: проверим текущие значения в базе данных непосредственно
        $currentEstimate = \Illuminate\Support\Facades\DB::table('estimates')->where('id', $this->id)->first();
        
        // Логируем текущее значение сметы из объекта и из базы
        \Illuminate\Support\Facades\Log::info("Смета #{$this->id}: project_id={$this->project_id}, type={$this->type}, status={$this->status}, total_amount=" . $this->total_amount . ", в БД total_amount=" . ($currentEstimate ? $currentEstimate->total_amount : 'not found'));
        
        // Проверяем тип сметы и исправляем, если он не установлен
        if (empty($this->type)) {
            // Пытаемся определить тип из JSON данных
            $detectedType = 'main'; // По умолчанию
            
            if (!empty($this->json_data)) {
                $jsonData = $this->json_data;
                
                // Если данные сохранены как строка, декодируем их
                if (is_string($jsonData)) {
                    $jsonData = json_decode($jsonData, true);
                }
                
                if (isset($jsonData['type'])) {
                    $detectedType = $jsonData['type'];
                } elseif (isset($jsonData['meta']['type'])) {
                    $detectedType = $jsonData['meta']['type'];
                } elseif (isset($jsonData['meta']['template_name'])) {
                    $templateName = strtolower($jsonData['meta']['template_name']);
                    if (strpos($templateName, 'материал') !== false) {
                        $detectedType = 'materials';
                    } elseif (strpos($templateName, 'дополнительн') !== false) {
                        $detectedType = 'additional';
                    }
                }
            }
            
            $this->type = $detectedType;
            \Illuminate\Support\Facades\DB::table('estimates')->where('id', $this->id)->update(['type' => $detectedType]);
            \Illuminate\Support\Facades\Log::info("Смета #{$this->id}: определен и установлен тип '{$detectedType}'");
        } elseif ($currentEstimate && empty($currentEstimate->type)) {
            \Illuminate\Support\Facades\DB::table('estimates')->where('id', $this->id)->update(['type' => $this->type]);
            \Illuminate\Support\Facades\Log::info("Смета #{$this->id}: обновлен тип в БД на '{$this->type}', так как он не был указан");
        } elseif ($currentEstimate && $currentEstimate->type != $this->type) {
            \Illuminate\Support\Facades\Log::warning("Смета #{$this->id}: несоответствие типов: в объекте '{$this->type}', в БД '{$currentEstimate->type}'");
            // Синхронизируем с базой данных
            $this->type = $currentEstimate->type;
        }
        
        // Получаем все элементы сметы для расчета total_amount
        $estimateItems = \Illuminate\Support\Facades\DB::table('estimate_items')->where('estimate_id', $this->id)->get();
        $calculatedTotal = $estimateItems->sum('client_cost');
        
        \Illuminate\Support\Facades\Log::info("Смета #{$this->id}: сумма client_cost всех позиций = {$calculatedTotal}. Количество позиций: " . $estimateItems->count());
        
        // Если в БД есть total_amount, но нет позиций сметы, используем значение из БД
        if ($estimateItems->count() == 0 && $currentEstimate && $currentEstimate->total_amount > 0) {
            $calculatedTotal = $currentEstimate->total_amount;
            \Illuminate\Support\Facades\Log::info("Смета #{$this->id}: в смете нет позиций, но есть total_amount в БД = {$calculatedTotal}, используем его");
        }
        
        // Обновляем текущую смету в базе, если расчетная сумма не совпадает с total_amount
        if ($calculatedTotal != $this->total_amount) {
            \Illuminate\Support\Facades\Log::info("Смета #{$this->id}: обнаружено расхождение сумм. Обновляем total_amount с " . $this->total_amount . " на {$calculatedTotal}");
            \Illuminate\Support\Facades\DB::table('estimates')->where('id', $this->id)->update([
                'total_amount' => $calculatedTotal,
                'status' => 'created' // Устанавливаем статус created для гарантии
            ]);
            $this->total_amount = $calculatedTotal; // Обновляем значение в текущем объекте
            $this->status = 'created'; // Обновляем значение в текущем объекте
        } else {
            // Проверим и установим статус в любом случае, если он отличается от 'created'
            if ($this->status != 'created' && $currentEstimate && $currentEstimate->status != 'created') {
                \Illuminate\Support\Facades\Log::info("Смета #{$this->id}: изменение статуса с '{$this->status}' на 'created'");
                \Illuminate\Support\Facades\DB::table('estimates')->where('id', $this->id)->update(['status' => 'created']);
                $this->status = 'created';
            }
        }
        
        // Получаем все сметы для этого проекта с учетными статусами напрямую из базы
        // Суммируем значения для основных смет (работы)
        $mainEstimates = \Illuminate\Support\Facades\DB::table('estimates')
            ->where('project_id', $this->project_id)
            ->where('type', 'main')
            ->whereIn('status', ['created', 'draft', 'pending', 'approved'])
            ->get();
            
        $mainEstimateAmount = $mainEstimates->sum('total_amount');
        
        // Логируем основные сметы
        \Illuminate\Support\Facades\Log::info("Основные сметы для проекта #{$this->project_id}: " . 
            collect($mainEstimates)->map(function($est) {
                return "ID=" . ($est->id ?? 'unknown') . 
                       ", total=" . ($est->total_amount ?? '0') . 
                       ", status=" . ($est->status ?? 'unknown');
            })->join('; '));
            
        // Суммируем значения для смет материалов
        $materialsEstimates = \Illuminate\Support\Facades\DB::table('estimates')
            ->where('project_id', $this->project_id)
            ->where('type', 'materials')
            ->whereIn('status', ['created', 'draft', 'pending', 'approved'])
            ->get();
            
        $materialsEstimateAmount = $materialsEstimates->sum('total_amount');
        
        // Логируем сметы материалов
        \Illuminate\Support\Facades\Log::info("Сметы материалов для проекта #{$this->project_id}: " . 
            collect($materialsEstimates)->map(function($est) {
                return "ID=" . ($est->id ?? 'unknown') . 
                       ", total=" . ($est->total_amount ?? '0') . 
                       ", status=" . ($est->status ?? 'unknown');
            })->join('; '));
        
        // Получаем текущие значения в проекте для сравнения
        $oldWorkAmount = $project->work_amount ?? 0;
        $oldMaterialsAmount = $project->materials_amount ?? 0;
        
        // Обновляем суммы в проекте
        $project->work_amount = $mainEstimateAmount;
        $project->materials_amount = $materialsEstimateAmount;
        
        // Если суммы не изменились, не делаем обновление
        if ($project->work_amount == $oldWorkAmount && $project->materials_amount == $oldMaterialsAmount) {
            \Illuminate\Support\Facades\Log::info("Проект #{$project->id}: суммы не изменились, обновление не требуется");
            $result = true;
        } else {
            // Пробуем обновить сначала через запрос к БД для надежности
            try {
                $updateResult = \Illuminate\Support\Facades\DB::table('projects')
                    ->where('id', $project->id)
                    ->update([
                        'work_amount' => $mainEstimateAmount,
                        'materials_amount' => $materialsEstimateAmount,
                        'updated_at' => now()
                    ]);
                    
                \Illuminate\Support\Facades\Log::info("Проект #{$project->id}: прямое обновление БД результат={$updateResult}");
                
                // Обновляем проект и через Eloquent для согласованности
                $result = $project->save();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Проект #{$project->id}: ошибка при обновлении: " . $e->getMessage());
                // Пытаемся обновить только через Eloquent
                $result = $project->save();
            }
        }
        
        // Проверяем, что значения реально обновились в базе
        $updatedProject = $project->fresh();
        
        // Получаем дополнительные данные о проекте
        $projectDetails = \Illuminate\Support\Facades\DB::table('projects')
            ->where('id', $project->id)
            ->first();
            
        // Записываем в лог информацию об обновлении сумм
        \Illuminate\Support\Facades\Log::info("Проект #{$project->id}: попытка обновления сумм результат=$result. " . 
            "Старые значения: работы={$oldWorkAmount}, материалы={$oldMaterialsAmount}. " .
            "Новые значения: работы={$mainEstimateAmount}, материалы={$materialsEstimateAmount}. " .
            "Проверка в базе: работы=" . ($updatedProject ? $updatedProject->work_amount : 'unknown') . 
            ", материалы=" . ($updatedProject ? $updatedProject->materials_amount : 'unknown') . 
            ", прямой запрос к БД: работы=" . ($projectDetails ? $projectDetails->work_amount : 'unknown') . 
            ", материалы=" . ($projectDetails ? $projectDetails->materials_amount : 'unknown'));
        \Illuminate\Support\Facades\Log::info("Проект #{$project->id}: обновлены суммы. Работы: {$mainEstimateAmount}, материалы: {$materialsEstimateAmount}. Тип текущей сметы: {$this->type}, статус: {$this->status}");
        
        return true;
    }
    
    /**
     * Получить форматированный размер файла
     *
     * @return string|null
     */
    public function getFileSizeFormattedAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }
        
        $size = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
    
    /**
     * Получить строковое представление типа сметы
     *
     * @return string
     */
    public function getTypeTextAttribute(): string
    {
        $types = [
            'main' => 'Основная смета',
            'additional' => 'Дополнительная смета',
            'materials' => 'Смета по материалам',
        ];
        
        return $types[$this->type] ?? $this->type;
    }
    
    /**
     * Получить строковое представление статуса сметы
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'draft' => 'Черновик',
            'pending' => 'На согласовании',
            'approved' => 'Утверждена',
            'rejected' => 'Отклонена',
            'created' => 'Создана',
        ];
        
        return $statuses[$this->status] ?? $this->status;
    }
    
    /**
     * Проверяет и обновляет статус сметы, если это необходимо
     * Если статус сметы не установлен или не входит в список допустимых, устанавливает 'created'
     *
     * @return bool
     */
    public function ensureValidStatus()
    {
        $validStatuses = ['draft', 'pending', 'approved', 'rejected', 'created'];
        
        if (!$this->status || !in_array($this->status, $validStatuses)) {
            $this->status = 'created';
            $this->save();
            \Illuminate\Support\Facades\Log::info("Смета #{$this->id}: установлен статус 'created'");
            return true;
        }
        
        return false;
    }
    
    /**
     * Статус сметы для отображения.
     *
     * @return string
     */
    public function statusBadge(): string
    {
        switch ($this->status) {
            case 'draft':
                return '<span class="badge bg-secondary">Черновик</span>';
            case 'pending':
                return '<span class="badge bg-warning text-dark">На рассмотрении</span>';
            case 'approved':
                return '<span class="badge bg-success">Утверждена</span>';
            case 'rejected':
                return '<span class="badge bg-danger">Отклонена</span>';
            case 'created':
                return '<span class="badge bg-success">Создана</span>';
            default:
                return '<span class="badge bg-info">Неизвестно</span>';
        }
    }
    
    /**
     * Тип сметы для отображения.
     *
     * @return string
     */
    public function typeBadge(): string
    {
        switch ($this->type) {
            case 'main':
                return '<span class="badge bg-primary">Основная</span>';
            case 'additional':
                return '<span class="badge bg-info">Дополнительная</span>';
            case 'materials':
                return '<span class="badge bg-dark">Материалы</span>';
            default:
                return '<span class="badge bg-secondary">Неизвестно</span>';
        }
    }
    
    /**
     * Проверить, доступен ли файл сметы для скачивания.
     *
     * @return bool
     */
    public function hasFile(): bool
    {
        return $this->file_path && Storage::disk('public')->exists($this->file_path);
    }
    
    /**
     * URL для скачивания файла сметы.
     *
     * @return string|null
     */
    public function downloadUrl(): ?string
    {
        return $this->hasFile() ? route('partner.estimates.export', $this->id) : null;
    }

    /**
     * Получить общую сумму из JSON данных
     *
     * @return float
     */
    public function getTotalFromJsonData(): float
    {
        if (!$this->json_data || !isset($this->json_data['totals'])) {
            return 0.0;
        }

        return (float) ($this->json_data['totals']['grand_total'] ?? 0);
    }

    /**
     * Обновить общую сумму на основе JSON данных
     *
     * @return void
     */
    public function updateTotalFromJsonData(): void
    {
        $total = $this->getTotalFromJsonData();
        $this->update(['total_amount' => $total]);
    }

    /**
     * Получить все элементы из JSON данных
     *
     * @return array
     */
    public function getItemsFromJsonData(): array
    {
        if (!$this->json_data || !isset($this->json_data['sections'])) {
            return [];
        }

        $items = [];
        foreach ($this->json_data['sections'] as $section) {
            if (isset($section['items']) && is_array($section['items'])) {
                $items = array_merge($items, $section['items']);
            }
        }

        return $items;
    }

    /**
     * Получить количество элементов в JSON данных
     *
     * @return int
     */
    public function getItemsCountFromJsonData(): int
    {
        return count($this->getItemsFromJsonData());
    }

    /**
     * Проверить, есть ли JSON данные
     *
     * @return bool
     */
    public function hasJsonData(): bool
    {
        return !empty($this->json_data) && is_array($this->json_data);
    }

    /**
     * Получить структуру столбцов из JSON данных
     *
     * @return array
     */
    public function getColumnsFromJsonData(): array
    {
        if (!$this->json_data || !isset($this->json_data['structure']['columns'])) {
            return [];
        }

        return $this->json_data['structure']['columns'];
    }

    /**
     * Получить настройки из JSON данных
     *
     * @return array
     */
    public function getSettingsFromJsonData(): array
    {
        if (!$this->json_data || !isset($this->json_data['structure']['settings'])) {
            return [];
        }

        return $this->json_data['structure']['settings'];
    }

    /**
     * Обновить JSON данные сметы
     *
     * @param array $data
     * @return bool
     */
    public function updateJsonData(array $data): bool
    {
        $this->json_data = $data;
        
        // Автоматически обновляем общую сумму
        if (isset($data['totals']['grand_total'])) {
            $this->total_amount = (float) $data['totals']['grand_total'];
        }

        return $this->save();
    }

    /**
     * Получить путь к JSON файлу данных
     *
     * @return string
     */
    public function getJsonDataFilePath(): string
    {
        return "estimates/{$this->project_id}/{$this->id}/data.json";
    }

    /**
     * Сохранить JSON данные в файл
     *
     * @return bool
     */
    public function saveJsonDataToFile(): bool
    {
        if (!$this->hasJsonData()) {
            return false;
        }

        $path = $this->getJsonDataFilePath();
        
        // Добавляем мета-данные
        $dataToSave = $this->json_data;
        $dataToSave['meta'] = array_merge($dataToSave['meta'] ?? [], [
            'estimate_id' => $this->id,
            'estimate_name' => $this->name,
            'project_id' => $this->project_id,
            'updated_at' => now()->toISOString(),
            'type' => $this->type,
            'version' => '1.0'
        ]);

        return Storage::put($path, json_encode($dataToSave, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Загрузить JSON данные из файла
     *
     * @return array|null
     */
    public function loadJsonDataFromFile(): ?array
    {
        $path = $this->getJsonDataFilePath();
        
        if (!Storage::exists($path)) {
            return null;
        }

        $content = Storage::get($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    /**
     * Синхронизировать JSON данные с файлом
     *
     * @return bool
     */
    public function syncJsonDataWithFile(): bool
    {
        if ($this->hasJsonData()) {
            return $this->saveJsonDataToFile();
        }

        $fileData = $this->loadJsonDataFromFile();
        if ($fileData) {
            return $this->updateJsonData($fileData);
        }

        return false;
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saved(function ($estimate) {
            // Создаем или обновляем PDF для клиента только если смета сохранена партнером
            // и имеет проект для привязки
            if ($estimate->project_id && $estimate->total_amount > 0) {
                try {
                    $pdfService = app(EstimateClientPdfService::class);
                    $pdfService->createOrUpdateClientPdf($estimate);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Ошибка создания PDF для клиента при сохранении сметы', [
                        'estimate_id' => $estimate->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });
    }
}
