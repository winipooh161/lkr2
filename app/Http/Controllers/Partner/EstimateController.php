<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Models\Project;
use App\Models\User;
use App\Services\SmsService;
use App\Services\EstimateJsonTemplateService;
use App\Services\EstimateClientPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EstimateController extends Controller
{
    protected $excelController;
    protected $smsService;
    protected $jsonTemplateService;
    protected $pdfService;
    
    /**
     * Конструктор с внедрением зависимостей
     */
    public function __construct(
        EstimateExcelController $excelController, 
        SmsService $smsService,
        EstimateJsonTemplateService $jsonTemplateService,
        EstimateClientPdfService $pdfService
    ) {
        $this->excelController = $excelController;
        $this->smsService = $smsService;
        $this->jsonTemplateService = $jsonTemplateService;
        $this->pdfService = $pdfService;
    }
    
    /**
     * Отображает список смет
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Estimate::query();
        
        // Если пользователь администратор, показываем все сметы
        if ($user->isAdmin()) {
            $query->with('project');
        } 
        // Если пользователь сметчик, показываем только его собственные сметы
        elseif ($user->isEstimator()) {
            // Сметчик видит только сметы, созданные им самим
            $query->where('user_id', $user->id)->with('project');
        } 
        // Если пользователь партнер, показываем сметы его проектов + сметы его сметчиков
        else {
            // Получаем ID проектов партнера
            $projectIds = Project::where('partner_id', $user->id)->pluck('id');
            
            // Получаем ID сметчиков партнера
            $estimatorIds = \App\Models\User::where('partner_id', $user->id)
                ->where('role', 'estimator')
                ->pluck('id');
            
            // Показываем сметы проектов партнера ИЛИ сметы, созданные его сметчиками
            $query->where(function($q) use ($projectIds, $estimatorIds, $user) {
                $q->whereIn('project_id', $projectIds)
                  ->orWhereIn('user_id', $estimatorIds)
                  ->orWhere('user_id', $user->id); // плюс собственные сметы партнера
            })->with('project');
        }
        
        // Применяем фильтры
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Получение списка смет с пагинацией
        $estimates = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Получаем список проектов для фильтра в зависимости от роли
        if ($user->isAdmin()) {
            $projects = Project::orderBy('client_name')->get(['id', 'client_name', 'address']);
        } elseif ($user->isEstimator()) {
            // Сметчик видит только назначенные ему проекты от своего партнера
            $projects = Project::where('estimator_id', $user->id)
                            ->whereHas('partner', function($query) use ($user) {
                                $query->where('id', $user->partner_id);
                            })
                            ->orderBy('client_name')
                            ->get(['id', 'client_name', 'address']);
        } else {
            // Партнер видит все свои проекты
            $projects = Project::where('partner_id', $user->id)
                            ->orderBy('client_name')
                            ->get(['id', 'client_name', 'address']);
        }
        
        // Для AJAX-запросов возвращаем только часть представления
        if ($request->ajax() || $request->wantsJson()) {
            return view('partner.estimates.partials.estimates-list', compact('estimates'))->render();
        }
        
        return view('partner.estimates.index', compact('estimates', 'projects'));
    }

    /**
     * Показывает форму для создания новой сметы
     */
    public function create()
    {
        $user = Auth::user();
        
        // Получаем проекты для выпадающего списка в зависимости от роли пользователя
        if ($user->role === 'admin') {
            $projects = Project::orderBy('client_name')->get();
        } elseif ($user->role === 'estimator') {
            // Сметчик видит только назначенные ему проекты от своего партнера
            $projects = Project::where('estimator_id', $user->id)
                            ->whereHas('partner', function($query) use ($user) {
                                $query->where('id', $user->partner_id);
                            })
                            ->orderBy('client_name')
                            ->get();
        } else {
            // Партнеры видят все свои проекты
            $projects = Project::where('partner_id', $user->id)
                            ->orderBy('client_name')
                            ->get();
        }
        
        // Получаем доступные типы шаблонов
        $templateTypes = $this->jsonTemplateService->getAvailableTypes();
        
        return view('partner.estimates.create', compact('projects', 'templateTypes'));
    }

    /**
     * Сохраняет новую смету в хранилище
     */
    public function store(Request $request)
    {
        // Валидация входных данных
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'type' => 'required|in:main,additional,materials',
            'status' => 'required|in:draft,pending,approved,created', 
            'notes' => 'nullable|string',
        ]);
        
        // Создание новой сметы
        $estimate = new Estimate();
        $estimate->name = $validatedData['name'];
        $estimate->project_id = $validatedData['project_id'];
        $estimate->type = $validatedData['type'];
        $estimate->status = $validatedData['status'];
        $estimate->description = $validatedData['notes'] ?? null;
        $estimate->user_id = Auth::id();
        
        // Создаем JSON данные сметы на основе шаблона
        $jsonData = $this->jsonTemplateService->createEstimateData($estimate);
        $estimate->json_data = $jsonData;
        
        $estimate->save();
        
        // Сохраняем JSON данные в файл (для резервного копирования)
        $this->jsonTemplateService->saveEstimateData($estimate, $jsonData);
        
        // Создаем PDF для клиента при создании новой сметы с проектом
        if ($estimate->project_id) {
            try {
                $pdfCreated = $this->pdfService->createOrUpdateClientPdf($estimate);
                if ($pdfCreated) {
                    Log::info('PDF смета для клиента создана при создании сметы', [
                        'estimate_id' => $estimate->id,
                        'project_id' => $estimate->project_id
                    ]);
                }
            } catch (\Exception $pdfError) {
                Log::warning('Не удалось создать PDF смету для клиента при создании', [
                    'estimate_id' => $estimate->id,
                    'error' => $pdfError->getMessage()
                ]);
            }
        }
        
        // Если смету создает сметчик и у него есть партнер, отправляем SMS партнеру
        $user = Auth::user();
        if ($user->role === 'estimator' && $user->partner_id) {
            $partner = User::find($user->partner_id);
            if ($partner && $partner->phone) {
                $projectInfo = '';
                if ($validatedData['project_id']) {
                    $project = Project::find($validatedData['project_id']);
                    if ($project) {
                        $projectInfo = $project->client_name . ' (' . $project->address . ')';
                    }
                }
                
                $this->smsService->sendEstimateNotificationToPartner(
                    $partner->phone,
                    $user->name ?? 'Сметчик',
                    $validatedData['name'],
                    $projectInfo
                );
            }
        }
        
        // Создаем шаблон Excel для сметы через специализированный контроллер
        $this->excelController->createInitialExcelFile($estimate);
        
        // Обновляем суммы в проекте на основе данных сметы
        $estimate->updateProjectAmounts();
        
        return redirect()->route('partner.estimates.edit', $estimate)
                         ->with('success', 'Смета успешно создана. Теперь вы можете заполнить ее данными.');
    }

    /**
     * Отображает указанную смету
     */
    public function show(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        // Получаем данные Excel, если файл существует
        $excelData = null;
        
        return view('partner.estimates.show', compact('estimate', 'excelData'));
    }

    /**
     * Показывает форму для редактирования указанной сметы
     */
    public function edit(Estimate $estimate)
    {
        $this->authorize('update', $estimate);
        
        $user = Auth::user();
        
        // Получаем проекты для выпадающего списка в зависимости от роли пользователя
        if ($user->role === 'admin') {
            $projects = Project::orderBy('client_name')->get();
        } elseif ($user->role === 'estimator') {
            // Сметчик видит только назначенные ему проекты от своего партнера
            $projects = Project::where('estimator_id', $user->id)
                            ->whereHas('partner', function($query) use ($user) {
                                $query->where('id', $user->partner_id);
                            })
                            ->orderBy('client_name')
                            ->get();
        } else {
            // Партнеры видят все свои проекты
            $projects = Project::where('partner_id', $user->id)
                            ->orderBy('client_name')
                            ->get();
        }
        
        return view('partner.estimates.edit', compact('estimate', 'projects'));
    }

    /**
     * Показывает редактор сметы
     *
     * @param Estimate $estimate Смета для редактирования
     * @return \Illuminate\View\View
     */
    public function editor(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        return view('estimates.editor', [
            'estimate' => $estimate,
            'estimateId' => $estimate->id,
            'estimateType' => $estimate->type ?? 'main'
        ]);
    }

    /**
     * Удаляет указанную смету из хранилища
     */
    public function destroy(Estimate $estimate)
    {
        $this->authorize('delete', $estimate);
        
        // Удаляем файл Excel, если он существует
        if ($estimate->file_path && Storage::disk('public')->exists($estimate->file_path)) {
            Storage::disk('public')->delete($estimate->file_path);
        }
        
        // Удаляем смету и связанные элементы
        $estimate->items()->delete();
        $estimate->delete();
        
        return redirect()->route('partner.estimates.index')
                         ->with('success', 'Смета успешно удалена.');
    }

    /**
     * Получить JSON данные сметы для редактора
     *
     * @param  Estimate  $estimate
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Estimate $estimate)
    {
        try {
            // Проверка прав доступа
            $this->authorize('view', $estimate);
            
            Log::info('Запрос на получение данных сметы', [
                'estimate_id' => $estimate->id,
                'user_id' => auth()->id(),
            ]);

            // Получаем данные сметы из базы данных
            $jsonData = $estimate->json_data;
            
            // Если данные пустые или некорректные, создаем/исправляем их
            if (empty($jsonData) || !isset($jsonData['sheets']) || !is_array($jsonData['sheets'])) {
                Log::info('Данные сметы пустые или некорректные, создаем новые', [
                    'estimate_id' => $estimate->id,
                    'estimate_type' => $estimate->type
                ]);
                
                $jsonData = $this->jsonTemplateService->createEstimateData($estimate);
                $estimate->json_data = $jsonData;
                $estimate->save();
                
                // Сохраняем JSON данные в файл (для резервного копирования)
                $this->jsonTemplateService->saveEstimateData($estimate, $jsonData);
            }
            
            return response()->json($jsonData);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении данных сметы: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'exception' => $e
            ]);
            
            return response()->json([
                'error' => 'Произошла ошибка при загрузке данных сметы',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : []
            ], 500);
        }
    }
    
    /**
     * Сохранение JSON-данных сметы
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Estimate  $estimate
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveJsonData(Request $request, Estimate $estimate)
    {
        try {
            // Проверка прав доступа
            $this->authorize('update', $estimate);
            
            Log::info('Запрос на сохранение JSON-данных сметы', [
                'estimate_id' => $estimate->id,
                'user_id' => auth()->id(),
            ]);
            
            // Проверка наличия данных
            $data = $request->all();
            
            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Отсутствуют данные для сохранения'
                ], 400);
            }
            
            // Определяем тип сметы из данных, если он указан
            if (isset($data['type']) && !empty($data['type'])) {
                $estimate->type = $data['type'];
            } elseif (isset($data['meta']['type']) && !empty($data['meta']['type'])) {
                $estimate->type = $data['meta']['type'];
            }
            
            // Рассчитываем общую сумму сметы
            $totalAmount = $this->calculateTotalAmount($data);
            $estimate->total_amount = $totalAmount;
            
            // Обновляем метаданные
            if (!isset($data['meta'])) {
                $data['meta'] = [];
            }
            
            $data['meta']['estimate_id'] = $estimate->id;
            $data['meta']['updated_at'] = now()->toISOString();
            $data['meta']['updated_by'] = auth()->user()->name;
            $data['meta']['type'] = $estimate->type;
            
            // Сохраняем данные в модель и в файл
            $estimate->json_data = $data;
            $estimate->save();
            
            // Используем сервис для корректного сохранения с синхронизацией
            $saved = $this->jsonTemplateService->updateEstimateData($estimate, $data);
            
            // Обновляем суммы в проекте на основе типа сметы
            $estimate->updateProjectAmounts();
            
            // Автоматически создаем/обновляем PDF для клиента
            if ($estimate->project_id) {
                try {
                    $pdfCreated = $this->pdfService->createOrUpdateClientPdf($estimate);
                    if ($pdfCreated) {
                        Log::info('PDF смета для клиента обновлена при сохранении данных', [
                            'estimate_id' => $estimate->id,
                            'project_id' => $estimate->project_id
                        ]);
                    }
                } catch (\Exception $pdfError) {
                    Log::warning('Не удалось обновить PDF смету для клиента', [
                        'estimate_id' => $estimate->id,
                        'error' => $pdfError->getMessage()
                    ]);
                }
            }
            
            // Записываем в лог успешное сохранение
            Log::info('JSON-данные сметы успешно сохранены', [
                'estimate_id' => $estimate->id,
                'estimate_type' => $estimate->type,
                'total_amount' => $estimate->total_amount,
                'saved_to_file' => $saved,
                'data_size' => strlen(json_encode($data))
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Данные сметы успешно сохранены',
                'estimate' => [
                    'id' => $estimate->id,
                    'type' => $estimate->type,
                    'total_amount' => $estimate->total_amount
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при сохранении JSON-данных сметы: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'exception' => $e,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при сохранении данных: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Обновить смету с JSON данными
     *
     * @param  Request  $request
     * @param  Estimate  $estimate
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Estimate $estimate)
    {
        try {
            // Проверка прав доступа
            $this->authorize('update', $estimate);
            
            // Валидация данных
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string',
                'project_id' => 'sometimes|nullable|exists:projects,id',
                'type' => 'sometimes|string|in:main,additional,materials',
                'status' => 'sometimes|string|in:draft,pending,approved,rejected',
                'sheets' => 'required|array',
                'currentSheet' => 'required|integer|min:0'
            ]);
            
            // Обновляем основные поля если они переданы
            if (isset($validatedData['name'])) {
                $estimate->name = $validatedData['name'];
            }
            
            if (isset($validatedData['description'])) {
                $estimate->description = $validatedData['description'];
            }
            
            if (isset($validatedData['project_id'])) {
                $estimate->project_id = $validatedData['project_id'];
            }
            
            if (isset($validatedData['type'])) {
                $estimate->type = $validatedData['type'];
            }
            
            if (isset($validatedData['status'])) {
                $estimate->status = $validatedData['status'];
            }
            
            // Формируем JSON данные
            $jsonData = [
                'sheets' => $request->input('sheets'),
                'currentSheet' => $request->input('currentSheet'),
                'meta' => [
                    'estimate_id' => $estimate->id,
                    'updated_at' => now()->toISOString(),
                    'type' => $estimate->type,
                    'version' => '1.0'
                ]
            ];
            
            // Рассчитываем общую сумму сметы для сохранения в базу
            $totalAmount = $this->calculateTotalAmount($jsonData);
            $estimate->total_amount = $totalAmount;
            
            // Сохраняем JSON данные в базу данных
            $estimate->json_data = $jsonData;
            $estimate->save();
            
            // Также сохраняем в файл для резервного копирования
            $this->jsonTemplateService->saveEstimateData($estimate, $jsonData);
            
            // Обновляем суммы в проекте на основе типа сметы
            $estimate->updateProjectAmounts();
            
            // Автоматически создаем/обновляем PDF для клиента
            try {
                $pdfCreated = $this->pdfService->createOrUpdateClientPdf($estimate);
                if ($pdfCreated) {
                    Log::info('PDF смета для клиента успешно создана/обновлена', [
                        'estimate_id' => $estimate->id,
                        'project_id' => $estimate->project_id
                    ]);
                }
            } catch (\Exception $pdfError) {
                Log::warning('Не удалось создать PDF смету для клиента', [
                    'estimate_id' => $estimate->id,
                    'error' => $pdfError->getMessage()
                ]);
                // Не прерываем основной процесс, если PDF не создался
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Смета успешно обновлена',
                'estimate' => [
                    'id' => $estimate->id,
                    'name' => $estimate->name,
                    'total_amount' => $estimate->total_amount,
                    'updated_at' => $estimate->updated_at->format('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при обновлении сметы', [
                'estimate_id' => $estimate->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении сметы: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обновление суммы проекта на основе типа сметы и итоговой суммы
     * 
     * @param Request $request
     * @param Estimate $estimate
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProjectAmount(Request $request, Estimate $estimate)
    {
        try {
            // Проверка прав доступа
            $this->authorize('update', $estimate);
            
            // Валидация входных данных
            $validated = $request->validate([
                'type' => 'required|string|in:main,materials',
                'total_amount' => 'required|numeric|min:0'
            ]);
            
            // Получаем проект
            $project = $estimate->project;
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Проект не найден'
                ], 404);
            }
            
            \Illuminate\Support\Facades\Log::info('Запрос на обновление суммы проекта', [
                'estimate_id' => $estimate->id,
                'project_id' => $project->id,
                'type' => $validated['type'],
                'total_amount' => $validated['total_amount']
            ]);
            
            // Обновляем соответствующее поле проекта в зависимости от типа сметы
            if ($validated['type'] === 'main') {
                $project->work_amount = $validated['total_amount'];
                \Illuminate\Support\Facades\Log::info('Обновляем work_amount проекта', [
                    'project_id' => $project->id,
                    'old_value' => $project->getOriginal('work_amount'),
                    'new_value' => $validated['total_amount']
                ]);
            } elseif ($validated['type'] === 'materials') {
                $project->materials_amount = $validated['total_amount'];
                \Illuminate\Support\Facades\Log::info('Обновляем materials_amount проекта', [
                    'project_id' => $project->id,
                    'old_value' => $project->getOriginal('materials_amount'),
                    'new_value' => $validated['total_amount']
                ]);
            }
            
            // Сохраняем изменения в проекте
            $project->save();
            
            // Обновляем тип сметы, если он не установлен
            if (empty($estimate->type) || $estimate->type != $validated['type']) {
                $estimate->type = $validated['type'];
                $estimate->save();
                
                \Illuminate\Support\Facades\Log::info('Обновляем тип сметы', [
                    'estimate_id' => $estimate->id,
                    'old_type' => $estimate->getOriginal('type'),
                    'new_type' => $validated['type']
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Сумма проекта успешно обновлена'
            ]);
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав для выполнения этого действия'
            ], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации данных',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при обновлении суммы проекта: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'exception' => $e,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении суммы проекта: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить базовую структуру сметы по типу
     *
     * @param  string  $type
     * @return array
     */
    private function getDefaultStructure(string $type): array
    {
        $structure = [
            'sheets' => [
                [
                    'name' => 'Основной',
                    'data' => []
                ]
            ],
            'currentSheet' => 0
        ];
        
        // В зависимости от типа сметы добавляем соответствующие разделы
        switch ($type) {
            case 'main': // Основная смета работ
                $structure['sheets'][0]['data'] = [
                    [
                        '_id' => uniqid(),
                        '_type' => 'header',
                        'name' => 'РАЗДЕЛ 1. ПОДГОТОВИТЕЛЬНЫЕ РАБОТЫ',
                        '_protected' => true
                    ],
                    [
                        '_id' => uniqid(),
                        'name' => 'Демонтаж старого покрытия',
                        'unit' => 'м2',
                        'quantity' => 1,
                        'price' => 350,
                        'sum' => '=quantity*price'
                    ],
                    [
                        '_id' => uniqid(),
                        '_type' => 'header',
                        'name' => 'РАЗДЕЛ 2. ОТДЕЛОЧНЫЕ РАБОТЫ',
                        '_protected' => true
                    ],
                    [
                        '_id' => uniqid(),
                        'name' => 'Шпатлевка стен',
                        'unit' => 'м2',
                        'quantity' => 1,
                        'price' => 250,
                        'sum' => '=quantity*price'
                    ]
                ];
                break;
                
            case 'additional': // Дополнительная смета
                $structure['sheets'][0]['data'] = [
                    [
                        '_id' => uniqid(),
                        '_type' => 'header',
                        'name' => 'ДОПОЛНИТЕЛЬНЫЕ РАБОТЫ',
                        '_protected' => true
                    ],
                    [
                        '_id' => uniqid(),
                        'name' => 'Добавьте дополнительные работы',
                        'unit' => 'раб',
                        'quantity' => 1,
                        'price' => 0,
                        'sum' => '=quantity*price'
                    ]
                ];
                break;
                
            case 'materials': // Смета материалов
                $structure['sheets'][0]['data'] = [
                    [
                        '_id' => uniqid(),
                        '_type' => 'header',
                        'name' => 'РАЗДЕЛ 1. ЧЕРНОВЫЕ МАТЕРИАЛЫ',
                        '_protected' => true
                    ],
                    [
                        '_id' => uniqid(),
                        'name' => 'Цемент М500',
                        'unit' => 'мешок',
                        'quantity' => 1,
                        'price' => 350,
                        'sum' => '=quantity*price'
                    ],
                    [
                        '_id' => uniqid(),
                        '_type' => 'header',
                        'name' => 'РАЗДЕЛ 2. ОТДЕЛОЧНЫЕ МАТЕРИАЛЫ',
                        '_protected' => true
                    ],
                    [
                        '_id' => uniqid(),
                        'name' => 'Шпатлевка финишная',
                        'unit' => 'кг',
                        'quantity' => 1,
                        'price' => 90,
                        'sum' => '=quantity*price'
                    ]
                ];
                break;
                
            default:
                // Пустая структура для других типов
                $structure['sheets'][0]['data'] = [
                    [
                        '_id' => uniqid(),
                        '_type' => 'header',
                        'name' => 'НОВЫЙ РАЗДЕЛ',
                        '_protected' => true
                    ],
                    [
                        '_id' => uniqid(),
                        'name' => 'Новая работа',
                        'unit' => 'раб',
                        'quantity' => 1,
                        'price' => 0,
                        'sum' => '=quantity*price'
                    ]
                ];
                break;
        }
        
        return $structure;
    }

    /**
     * Расчет общей суммы сметы
     *
     * @param  array  $jsonData
     * @return float
     */
    private function calculateTotalAmount(array $jsonData): float
    {
        $totalAmount = 0;
        
        // Сначала пробуем взять сумму из объекта totals
        if (isset($jsonData['totals'])) {
            $totals = $jsonData['totals'];
            
            // Для смет материалов используем client_materials_total или materials_total
            if (isset($jsonData['type']) && $jsonData['type'] === 'materials') {
                if (isset($totals['client_materials_total']) && $totals['client_materials_total'] > 0) {
                    return (float) $totals['client_materials_total'];
                } elseif (isset($totals['materials_total']) && $totals['materials_total'] > 0) {
                    return (float) $totals['materials_total'];
                }
            }
            
            // Для основных смет используем client_grand_total или grand_total
            if (isset($totals['client_grand_total']) && $totals['client_grand_total'] > 0) {
                return (float) $totals['client_grand_total'];
            } elseif (isset($totals['grand_total']) && $totals['grand_total'] > 0) {
                return (float) $totals['grand_total'];
            }
        }
        
        // Если итоги в объекте totals не найдены, ищем в footer
        if (isset($jsonData['footer']['items'])) {
            foreach ($jsonData['footer']['items'] as $item) {
                if (isset($item['_type']) && $item['_type'] === 'grand_total') {
                    if (isset($item['sum']) && is_numeric($item['sum'])) {
                        return (float) $item['sum'];
                    }
                }
            }
        }
        
        // Если есть листы, проходим по всем листам и ищем строку с общим итогом
        if (isset($jsonData['sheets'])) {
            foreach ($jsonData['sheets'] as $sheet) {
                if (!isset($sheet['data'])) continue;
                
                foreach ($sheet['data'] as $row) {
                    if (isset($row['_type']) && $row['_type'] === 'grand_total') {
                        // Используем результат расчета, если он есть
                        if (isset($row['_result_sum'])) {
                            return (float) $row['_result_sum'];
                        } else if (isset($row['sum'])) {
                            return (float) $row['sum'];
                        }
                    }
                }
            }
        
            // Если итоговая строка не найдена, считаем вручную
            foreach ($jsonData['sheets'] as $sheet) {
                if (!isset($sheet['data'])) continue;
                
                foreach ($sheet['data'] as $row) {
                    // Если это обычная строка (не заголовок и не итог)
                    if (!isset($row['_type'])) {
                        // Используем результат расчета или исходное значение суммы
                        if (isset($row['_result_sum'])) {
                            $totalAmount += (float) $row['_result_sum'];
                        } else if (isset($row['sum']) && !is_string($row['sum'])) {
                            $totalAmount += (float) $row['sum'];
                        } elseif (isset($row['client_cost']) && is_numeric($row['client_cost'])) {
                            $totalAmount += (float) $row['client_cost'];
                        }
                    }
                }
            }
        }
        
        return $totalAmount;
    }

    /**
     * Получение JSON шаблона для типа сметы
     */
    public function getTemplate(string $type)
    {
        try {
            $template = $this->jsonTemplateService->getTemplateByType($type);
            
            return response()->json($template);
            
        } catch (\Exception $e) {
            Log::error("Ошибка получения шаблона {$type}: " . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка загрузки шаблона',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить JSON-шаблон сметы по типу
     *
     * @param  string  $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTemplateByType($type)
    {
        try {
            // Проверяем, что тип валидный
            if (!in_array($type, ['main', 'additional', 'materials'])) {
                return response()->json([
                    'error' => 'Неверный тип сметы',
                    'message' => 'Допустимые типы: main, additional, materials'
                ], 400);
            }
            
            Log::info('Запрос на получение шаблона сметы', [
                'type' => $type,
                'user_id' => auth()->id()
            ]);
            
            // Получаем шаблон по типу
            $template = $this->jsonTemplateService->getTemplateByType($type);
            
            // Преобразуем в формат для редактора
            $editorData = [
                'sheets' => [
                    [
                        'name' => 'Основной',
                        'data' => $this->jsonTemplateService->generateDataFromTemplate($template),
                        'footer' => $template['footer']['items'] ?? []
                    ]
                ],
                'currentSheet' => 0,
                'meta' => [
                    'estimate_id' => null,
                    'estimate_name' => 'Новая смета',
                    'project_id' => null,
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString(),
                    'type' => $type,
                    'version' => '1.0',
                    'template_version' => $template['version'] ?? '1.0',
                    'is_template' => true
                ],
                'structure' => $template['structure'] ?? [],
                'totals' => $template['totals'] ?? [
                    'work_total' => 0,
                    'materials_total' => 0,
                    'grand_total' => 0,
                    'markup_percent' => 20,
                    'discount_percent' => 0
                ]
            ];
            
            return response()->json($editorData);
            
        } catch (\Exception $e) {
            Log::error('Ошибка при получении шаблона сметы: ' . $e->getMessage(), [
                'type' => $type,
                'exception' => $e
            ]);
            
            return response()->json([
                'error' => 'Произошла ошибка при загрузке шаблона сметы',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : []
            ], 500);
        }
    }

    /**
     * Создать PDF смету для клиента вручную
     *
     * @param Estimate $estimate
     * @return \Illuminate\Http\JsonResponse
     */
    public function createClientPdf(Estimate $estimate)
    {
        try {
            // Проверка прав доступа
            $this->authorize('update', $estimate);
            
            $pdfService = app(\App\Services\EstimateClientPdfService::class);
            $result = $pdfService->createOrUpdateClientPdf($estimate);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'PDF смета для клиента успешно создана'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось создать PDF смету для клиента'
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Ошибка при создании PDF сметы для клиента: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании PDF сметы: ' . $e->getMessage()
            ], 500);
        }
    }
}
