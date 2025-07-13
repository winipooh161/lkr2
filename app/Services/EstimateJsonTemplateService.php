<?php

namespace App\Services;

use App\Models\Estimate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EstimateJsonTemplateService
{
    /**
     * Основные шаблоны для разных типов смет
     */
    private const TEMPLATE_TYPES = [
        'main' => 'Основная смета',
        'additional' => 'Дополнительная смета',
        'materials' => 'Смета материалов'
    ];

    /**
     * Получить или создать JSON шаблон для типа сметы
     *
     * @param string $type Тип сметы
     * @return array JSON структура шаблона
     */
    public function getTemplateByType(string $type): array
    {
        // Загружаем шаблон напрямую из JSON файлов без кэширования
        $templatePath = storage_path("app/templates/estimates/{$type}.json");
        
        if (file_exists($templatePath)) {
            try {
                $content = file_get_contents($templatePath);
                $template = json_decode($content, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($template['structure']) && isset($template['sections'])) {
                    return $template;
                }
            } catch (\Exception $e) {
                Log::warning("Ошибка загрузки шаблона {$type}: " . $e->getMessage());
            }
        }
        
        // Если шаблон не найден или поврежден, создаем новый
        Log::warning("Template file not found or invalid: {$templatePath}, creating default template");
        return $this->createDefaultTemplate($type);
    }

    /**
     * Создать JSON данные для новой сметы на основе шаблона
     *
     * @param Estimate $estimate
     * @return array
     */
    public function createEstimateData(Estimate $estimate): array
    {
        $template = $this->getTemplateByType($estimate->type);
        
        // Генерируем данные из шаблона
        $sheetData = $this->generateDataFromTemplate($template);
        
        // Рассчитываем итоги на основе данных
        $totals = $this->calculateTotalFromData(['data' => $sheetData]);
        
        // Создаем структуру данных на основе шаблона
        $estimateData = [
            'type' => $estimate->type,
            'version' => '1.0',
            'meta' => [
                'estimate_id' => $estimate->id,
                'estimate_name' => $estimate->name,
                'project_id' => $estimate->project_id,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
                'template_name' => $template['meta']['template_name'] ?? 'Смета',
                'description' => $template['meta']['description'] ?? '',
                'template_version' => $template['version'] ?? '1.0'
            ],
            'structure' => $template['structure'] ?? [
                'columns' => [],
                'settings' => []
            ],
            'sections' => $template['sections'] ?? [],
            'sheets' => [
                [
                    'name' => 'Основной',
                    'data' => $sheetData
                ]
            ],
            'currentSheet' => 0,
            'totals' => [
                'work_total' => $totals,
                'materials_total' => 0,
                'grand_total' => $totals,
                'markup_percent' => 20,
                'discount_percent' => 0,
                'client_work_total' => 0,
                'client_materials_total' => 0,
                'client_grand_total' => 0
            ],
            'footer' => $template['footer'] ?? ['items' => []]
        ];
        
        return $estimateData;
    }

    /**
     * Генерация строк данных из JSON шаблона
     * 
     * @param array $template
     * @return array
     */
    public function generateDataFromTemplate(array $template): array
    {
        $data = [];
        $rowNumber = 1;
        
        // Если в шаблоне есть секции, генерируем данные из них
        if (isset($template['sections']) && is_array($template['sections'])) {
            foreach ($template['sections'] as $section) {
                // Добавляем заголовок секции
                $sectionTitle = $section['title'] ?? 'РАЗДЕЛ';
                $sectionTitle = $this->safeTruncateString($sectionTitle, 100);
                
                $data[] = [
                    '_id' => uniqid(),
                    '_type' => 'header',
                    'number' => '',
                    'name' => $sectionTitle,
                    'unit' => '',
                    'quantity' => '',
                    'price' => '',
                    'cost' => '',
                    'markup' => '',
                    'discount' => '',
                    'client_price' => '',
                    'client_cost' => '',
                    '_protected' => true,
                    '_section' => true
                ];
                
                // Добавляем элементы секции
                if (isset($section['items']) && is_array($section['items'])) {
                    foreach ($section['items'] as $item) {
                        $quantity = floatval($item['quantity'] ?? 1);
                        $price = floatval($item['price'] ?? 0);
                        $markup = floatval($item['markup'] ?? 20);
                        $discount = floatval($item['discount'] ?? 0);
                        
                        // Рассчитываем значения
                        $cost = $quantity * $price;
                        $clientPrice = $price * (1 + $markup/100) * (1 - $discount/100);
                        $clientCost = $quantity * $clientPrice;
                        
                        $rowData = [
                            '_id' => uniqid(),
                            'number' => $rowNumber++,
                            'name' => $this->safeTruncateString($item['name'] ?? 'Новая работа'),
                            'unit' => $item['unit'] ?? 'ед',
                            'quantity' => $quantity,
                            'price' => $price,
                            'cost' => $cost,
                            'markup' => $markup,
                            'discount' => $discount,
                            'client_price' => $clientPrice,
                            'client_cost' => $clientCost
                        ];
                        
                        $data[] = $rowData;
                    }
                }
                
                // Если в секции нет элементов, добавляем пустую строку
                if (!isset($section['items']) || empty($section['items'])) {
                    $data[] = [
                        '_id' => uniqid(),
                        'number' => $rowNumber++,
                        'name' => 'Добавьте работы в этот раздел',
                        'unit' => 'ед',
                        'quantity' => 1,
                        'price' => 0,
                        'cost' => 0,
                        'markup' => 20,
                        'discount' => 0,
                        'client_price' => 0,
                        'client_cost' => 0
                    ];
                }
            }
        }
        
        // Если секций нет, создаем базовую структуру
        if (empty($data)) {
            $data = $this->generateEstimateRows($template['type'] ?? 'main');
        }
        
        return $data;
    }

    /**
     * Генерация строк сметы в зависимости от типа (старый метод для обратной совместимости)
     */
    private function generateEstimateRows(string $type): array
    {
        switch ($type) {
            case 'main': // Основная смета работ
                return [
                    [
                        '_id' => uniqid(),
                        '_type' => 'header',
                        'name' => $this->safeTruncateString('РАЗДЕЛ 1. ПОДГОТОВИТЕЛЬНЫЕ РАБОТЫ'),
                        '_protected' => true
                    ],
                    [
                        '_id' => uniqid(),
                        'name' => $this->safeTruncateString('Демонтаж старого покрытия'),
                        'unit' => 'м2',
                        'quantity' => 1,
                        'price' => 350,
                        'sum' => '=quantity*price'
                    ],
                    [
                        '_id' => uniqid(),
                        '_type' => 'header',
                        'name' => $this->safeTruncateString('РАЗДЕЛ 2. ОТДЕЛОЧНЫЕ РАБОТЫ'),
                        '_protected' => true
                    ],
                    [
                        '_id' => uniqid(),
                        'name' => $this->safeTruncateString('Шпатлевка стен'),
                        'unit' => 'м2',
                        'quantity' => 1,
                        'price' => 250,
                        'sum' => '=quantity*price'
                    ]
                ];
                
            case 'additional': // Дополнительная смета
                return [
                    [
                        '_id' => uniqid(),
                        '_type' => 'header',
                        'name' => $this->safeTruncateString('ДОПОЛНИТЕЛЬНЫЕ РАБОТЫ'),
                        '_protected' => true
                    ],
                    [
                        '_id' => uniqid(),
                        'name' => $this->safeTruncateString('Добавьте дополнительные работы'),
                        'unit' => 'раб',
                        'quantity' => 1,
                        'price' => 0,
                        'sum' => '=quantity*price'
                    ]
                ];
                
            case 'materials': // Смета материалов
                return [
                    [
                        '_id' => uniqid(),
                        '_type' => 'header',
                        'name' => $this->safeTruncateString('РАЗДЕЛ 1. ЧЕРНОВЫЕ МАТЕРИАЛЫ'),
                        '_protected' => true
                    ],
                    [
                        '_id' => uniqid(),
                        'name' => $this->safeTruncateString('Цемент М500'),
                        'unit' => 'мешок',
                        'quantity' => 1,
                        'price' => 350,
                        'sum' => '=quantity*price'
                    ],
                    [
                        '_id' => uniqid(),
                        '_type' => 'header',
                        'name' => $this->safeTruncateString('РАЗДЕЛ 2. ОТДЕЛОЧНЫЕ МАТЕРИАЛЫ'),
                        '_protected' => true
                    ],
                    [
                        '_id' => uniqid(),
                        'name' => $this->safeTruncateString('Шпатлевка финишная'),
                        'unit' => 'кг',
                        'quantity' => 1,
                        'price' => 90,
                        'sum' => '=quantity*price'
                    ]
                ];
                
            default:
                return [
                    [
                        '_id' => uniqid(),
                        '_type' => 'header',
                        'name' => $this->safeTruncateString('НОВЫЙ РАЗДЕЛ'),
                        '_protected' => true
                    ],
                    [
                        '_id' => uniqid(),
                        'name' => $this->safeTruncateString('Новая работа'),
                        'unit' => 'раб',
                        'quantity' => 1,
                        'price' => 0,
                        'sum' => '=quantity*price'
                    ]
                ];
        }
    }

    /**
     * Сохранить данные сметы в JSON файл
     *
     * @param Estimate $estimate
     * @param array $data
     * @return bool
     */
    public function saveEstimateData(Estimate $estimate, array $data): bool
    {
        try {
            $filePath = $this->getEstimateDataPath($estimate);
            
            // Обновляем метаданные
            $data['meta']['updated_at'] = now()->toISOString();
            $data['meta']['estimate_id'] = $estimate->id;
            
            // Сохраняем в JSON файл
            $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            // Создаем директорию если не существует
            $directory = dirname($filePath);
            if (!Storage::disk('local')->exists($directory)) {
                Storage::disk('local')->makeDirectory($directory);
            }
            
            Storage::disk('local')->put($filePath, $jsonContent);
            
            // Обновляем запись в базе данных
            $estimate->update([
                'file_updated_at' => now(),
                'file_size' => strlen($jsonContent),
                'total_amount' => $this->calculateTotalFromData($data)
            ]);
            
            Log::info("Сохранены JSON данные сметы #{$estimate->id}", [
                'file_path' => $filePath,
                'size' => strlen($jsonContent)
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Ошибка сохранения JSON данных сметы #{$estimate->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Загрузить данные сметы из JSON файла
     *
     * @param Estimate $estimate
     * @return array|null
     */
    public function loadEstimateData(Estimate $estimate): ?array
    {
        try {
            // Сначала пытаемся получить данные из базы
            if (!empty($estimate->json_data)) {
                return $estimate->json_data;
            }
            
            // Если в базе нет данных, пытаемся загрузить из файла
            $filePath = $this->getEstimateDataPath($estimate);
            
            if (Storage::disk('local')->exists($filePath)) {
                $content = Storage::disk('local')->get($filePath);
                $data = json_decode($content, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Сохраняем загруженные данные в базу
                    $estimate->json_data = $data;
                    $estimate->save();
                    
                    return $data;
                }
            }
            
            // Если файл не существует или поврежден, создаем новый на основе шаблона
            $data = $this->createEstimateData($estimate);
            
            // Сохраняем и в базу, и в файл
            $estimate->json_data = $data;
            $estimate->save();
            $this->saveEstimateData($estimate, $data);
            
            return $data;
        } catch (\Exception $e) {
            Log::error("Ошибка загрузки JSON данных сметы #{$estimate->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Обновить JSON данные сметы
     *
     * @param Estimate $estimate
     * @param array $data
     * @return bool
     */
    public function updateEstimateData(Estimate $estimate, array $data): bool
    {
        try {
            // Обновляем метаданные
            $data['meta']['updated_at'] = now()->toISOString();
            $data['meta']['estimate_id'] = $estimate->id;
            
            // Синхронизируем данные между sections и sheets для обратной совместимости
            $data = $this->synchronizeSectionsAndSheets($data);
            
            // Сохраняем в базу данных
            $estimate->json_data = $data;
            $estimate->total_amount = $this->calculateTotalFromData($data);
            $estimate->save();
            
            // Также сохраняем в файл для резервного копирования
            $this->saveEstimateData($estimate, $data);
            
            Log::info("Обновлены JSON данные сметы #{$estimate->id}");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Ошибка обновления JSON данных сметы #{$estimate->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Создать шаблон по умолчанию для типа сметы
     *
     * @param string $type
     * @return array
     */
    private function createDefaultTemplate(string $type): array
    {
        $template = [
            'type' => $type,
            'version' => '1.0',
            'meta' => [
                'created_at' => now()->toISOString(),
                'template_name' => self::TEMPLATE_TYPES[$type] ?? 'Смета',
                'is_template' => true
            ],
            'structure' => [
                'columns' => $this->getDefaultColumns($type),
                'settings' => [
                    'readonly_columns' => [4, 7, 8], // Индексы колонок "Стоимость", "Цена клиента", "Сумма клиента"
                    'formula_columns' => [4, 7, 8],
                    'numeric_columns' => [1, 2, 3, 4, 5, 6, 7, 8],
                    'currency_columns' => [3, 4, 6, 7, 8]
                ]
            ],
            'sections' => $this->getDefaultSections($type),
            'totals' => [
                'work_total' => 0,
                'materials_total' => 0,
                'grand_total' => 0,
                'markup_percent' => 20,
                'discount_percent' => 0
            ]
        ];

        // Сохраняем шаблон в файл
        $templatePath = $this->getTemplatePath($type);
        try {
            Storage::disk('local')->put($templatePath, json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            Log::info("Создан новый JSON шаблон для типа '{$type}'");
        } catch (\Exception $e) {
            Log::error("Ошибка сохранения шаблона '{$type}': " . $e->getMessage());
        }

        return $template;
    }

    /**
     * Получить колонки по умолчанию для типа сметы
     */
    private function getDefaultColumns(string $type): array
    {
        $baseColumns = [
            ['title' => '№', 'width' => 50, 'type' => 'numeric'],
            ['title' => 'Наименование работ', 'width' => 300, 'type' => 'text'],
            ['title' => 'Ед.изм.', 'width' => 80, 'type' => 'text'],
            ['title' => 'Кол-во', 'width' => 80, 'type' => 'numeric'],
            ['title' => 'Цена', 'width' => 100, 'type' => 'currency'],
            ['title' => 'Стоимость', 'width' => 120, 'type' => 'currency', 'formula' => 'C{row}*D{row}'],
            ['title' => 'Наценка %', 'width' => 80, 'type' => 'numeric'],
            ['title' => 'Скидка %', 'width' => 80, 'type' => 'numeric'],
            ['title' => 'Цена клиента', 'width' => 120, 'type' => 'currency', 'formula' => 'D{row}*(1+F{row}/100)*(1-G{row}/100)'],
            ['title' => 'Сумма клиента', 'width' => 120, 'type' => 'currency', 'formula' => 'C{row}*H{row}']
        ];

        if ($type === 'materials') {
            // Для материалов изменяем некоторые названия колонок
            $baseColumns[1]['title'] = 'Наименование материала';
            $baseColumns[4]['title'] = 'Цена за ед.';
        }

        return $baseColumns;
    }

    /**
     * Получить разделы по умолчанию для типа сметы
     */
    private function getDefaultSections(string $type): array
    {
        switch ($type) {
            case 'materials':
                return [
                    [
                        'id' => 'materials_1',
                        'title' => 'ОСНОВНЫЕ МАТЕРИАЛЫ',
                        'type' => 'section',
                        'items' => []
                    ]
                ];
            
            case 'additional':
                return [
                    [
                        'id' => 'additional_1',
                        'title' => 'ДОПОЛНИТЕЛЬНЫЕ РАБОТЫ',
                        'type' => 'section',
                        'items' => []
                    ]
                ];
            
            default: // main
                return [
                    [
                        'id' => 'prep_1',
                        'title' => 'ПОДГОТОВИТЕЛЬНЫЕ РАБОТЫ',
                        'type' => 'section',
                        'items' => []
                    ],
                    [
                        'id' => 'main_1',
                        'title' => 'ОСНОВНЫЕ РАБОТЫ',
                        'type' => 'section',
                        'items' => []
                    ]
                ];
        }
    }

    /**
     * Вычислить общую сумму из данных JSON
     */
    private function calculateTotalFromData(array $data): float
    {
        $total = 0;
        
        // Проверяем структуру данных
        if (isset($data['data']) && is_array($data['data'])) {
            // Новая структура - данные в массиве data
            foreach ($data['data'] as $row) {
                if (isset($row['_type']) && $row['_type'] === 'header') {
                    continue; // Пропускаем заголовки секций
                }
                
                if (isset($row['client_cost']) && is_numeric($row['client_cost'])) {
                    $total += (float) $row['client_cost'];
                } elseif (isset($row['cost']) && is_numeric($row['cost'])) {
                    $total += (float) $row['cost'];
                }
            }
        } elseif (isset($data['sections'])) {
            // Старая структура - данные в секциях
            foreach ($data['sections'] as $section) {
                if (isset($section['items'])) {
                    foreach ($section['items'] as $item) {
                        if (isset($item['client_cost']) && is_numeric($item['client_cost'])) {
                            $total += (float) $item['client_cost'];
                        } elseif (isset($item['cost']) && is_numeric($item['cost'])) {
                            $total += (float) $item['cost'];
                        }
                    }
                }
            }
        }
        
        return $total;
    }

    /**
     * Получить путь к шаблону
     */
    private function getTemplatePath(string $type): string
    {
        return "templates/estimates/{$type}.json";
    }

    /**
     * Получить путь к данным сметы
     */
    private function getEstimateDataPath(Estimate $estimate): string
    {
        return "estimates/{$estimate->project_id}/{$estimate->id}/data.json";
    }

    /**
     * Получить список доступных типов шаблонов
     */
    public function getAvailableTypes(): array
    {
        return self::TEMPLATE_TYPES;
    }

    /**
     * Очистить кеш шаблонов
     */
    public function clearTemplateCache(): void
    {
        foreach (array_keys(self::TEMPLATE_TYPES) as $type) {
            Cache::forget("estimate_template_{$type}");
        }
    }

    /**
     * Безопасно обрезает строку до указанной длины для предотвращения ошибок в JavaScript
     * 
     * @param string $str Строка для проверки
     * @param int $maxLength Максимальная длина (по умолчанию 100)
     * @return string Безопасная строка
     */
    private function safeTruncateString(string $str, int $maxLength = 100): string
    {
        if (mb_strlen($str) > $maxLength) {
            return mb_substr($str, 0, $maxLength - 3) . '...';
        }
        
        return $str;
    }

    /**
     * Генерация стандартного футера сметы
     * 
     * @return array
     */
    public function generateDefaultFooter(): array
    {
        return [
            [
                'name' => 'Исполнитель',
                'value' => '',
                'type' => 'signature'
            ],
            [
                'name' => 'Заказчик',
                'value' => '',
                'type' => 'signature'
            ]
        ];
    }

    /**
     * Синхронизация данных между sections и sheets для обратной совместимости
     *
     * @param array $data
     * @return array
     */
    private function synchronizeSectionsAndSheets(array $data): array
    {
        try {
            // Если есть sections, создаем/обновляем sheets на их основе
            if (isset($data['sections']) && !empty($data['sections'])) {
                $sheetsData = $this->convertSectionsToSheetsData($data['sections']);
                
                // Обновляем или создаем sheets
                if (!isset($data['sheets'])) {
                    $data['sheets'] = [];
                }
                
                if (empty($data['sheets'])) {
                    $data['sheets'][] = [
                        'name' => 'Основной',
                        'data' => $sheetsData
                    ];
                } else {
                    $data['sheets'][0]['data'] = $sheetsData;
                }
                
                Log::info('Синхронизированы данные sections -> sheets', [
                    'sections_count' => count($data['sections']),
                    'sheets_data_count' => count($sheetsData)
                ]);
            }
            // Если есть только sheets, создаем sections на их основе
            elseif (isset($data['sheets']) && !empty($data['sheets']) && 
                    isset($data['sheets'][0]['data']) && !empty($data['sheets'][0]['data'])) {
                
                $data['sections'] = $this->convertSheetsDataToSections($data['sheets'][0]['data']);
                
                Log::info('Синхронизированы данные sheets -> sections', [
                    'sheets_data_count' => count($data['sheets'][0]['data']),
                    'sections_count' => count($data['sections'])
                ]);
            }
            
            return $data;
        } catch (\Exception $e) {
            Log::error('Ошибка синхронизации данных между sections и sheets: ' . $e->getMessage());
            return $data; // Возвращаем исходные данные в случае ошибки
        }
    }
    
    /**
     * Конвертация данных из sections в формат sheets
     *
     * @param array $sections
     * @return array
     */
    private function convertSectionsToSheetsData(array $sections): array
    {
        $sheetsData = [];
        
        foreach ($sections as $section) {
            if (!is_array($section) || !isset($section['title'])) continue;
            
            // Добавляем заголовок раздела
            $sheetsData[] = [
                '_id' => uniqid(),
                '_type' => 'header',
                'number' => '',
                'name' => $section['title'],
                'unit' => '',
                'quantity' => '',
                'price' => '',
                'cost' => '',
                'markup' => '',
                'discount' => '',
                'client_price' => '',
                'client_cost' => '',
                '_protected' => true,
                '_section' => true
            ];
            
            // Добавляем элементы раздела
            $items = $section['items'] ?? [];
            foreach ($items as $item) {
                if (is_array($item)) {
                    $sheetsData[] = array_merge([
                        '_id' => uniqid(),
                        'number' => '',
                        'name' => '',
                        'unit' => '',
                        'quantity' => 0,
                        'price' => 0,
                        'cost' => 0,
                        'markup' => 20,
                        'discount' => 0,
                        'client_price' => 0,
                        'client_cost' => 0
                    ], $item);
                }
            }
        }
        
        return $sheetsData;
    }
    
    /**
     * Конвертация данных из формата sheets в sections
     *
     * @param array $sheetsData
     * @return array
     */
    private function convertSheetsDataToSections(array $sheetsData): array
    {
        $sections = [];
        $currentSection = null;
        $currentSectionIndex = -1;
        
        foreach ($sheetsData as $row) {
            if (!is_array($row)) continue;
            
            // Если это заголовок раздела
            if (isset($row['_type']) && $row['_type'] === 'header') {
                // Начинаем новый раздел
                $currentSectionIndex++;
                $currentSection = [
                    'id' => 'section_' . $currentSectionIndex,
                    'title' => $row['name'] ?? 'Раздел ' . ($currentSectionIndex + 1),
                    'type' => 'section',
                    'items' => []
                ];
                $sections[] = &$currentSection;
            } else {
                // Это обычная строка данных
                if ($currentSection === null) {
                    // Если нет активного раздела, создаем раздел по умолчанию
                    $currentSectionIndex++;
                    $currentSection = [
                        'id' => 'section_' . $currentSectionIndex,
                        'title' => 'Основной раздел',
                        'type' => 'section',
                        'items' => []
                    ];
                    $sections[] = &$currentSection;
                }
                
                // Добавляем строку в текущий раздел
                $currentSection['items'][] = $row;
            }
        }
        
        return $sections;
    }
}
