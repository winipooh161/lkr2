<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EstimateTemplatesController extends Controller
{
    /**
     * Получение структуры разделов и работ для шаблона смет
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSectionsData()
    {
        try {
            // Получаем данные из кэша или из хранилища
            $cacheKey = 'estimate_templates_' . Auth::id();
            $data = Cache::remember($cacheKey, 60 * 24, function () {
                return $this->loadTemplateData();
            });
            
            return response()->json([
                'success' => true,
                'sections' => $data['sections'] ?? [],
                'works' => $data['works'] ?? []
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении данных разделов', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении данных: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Загрузка данных шаблона
     *
     * @return array
     */
    private function loadTemplateData(): array
    {
        // Здесь можно загрузить данные из базы данных или файла
        // В данном примере возвращаем фиксированные данные
        
        $sections = [
            [
                'title' => 'ПОДГОТОВИТЕЛЬНЫЕ РАБОТЫ',
                'items' => [
                    ['name' => 'Демонтаж старого покрытия', 'unit' => 'м2'],
                    ['name' => 'Подготовка поверхности', 'unit' => 'м2'],
                    ['name' => 'Вынос мусора', 'unit' => 'м3']
                ]
            ],
            [
                'title' => 'ОТДЕЛОЧНЫЕ РАБОТЫ',
                'items' => [
                    ['name' => 'Шпатлевка стен', 'unit' => 'м2'],
                    ['name' => 'Покраска стен', 'unit' => 'м2'],
                    ['name' => 'Поклейка обоев', 'unit' => 'м2']
                ]
            ],
            [
                'title' => 'НАПОЛЬНЫЕ РАБОТЫ',
                'items' => [
                    ['name' => 'Укладка ламината', 'unit' => 'м2'],
                    ['name' => 'Укладка плитки', 'unit' => 'м2'],
                    ['name' => 'Монтаж плинтусов', 'unit' => 'м.п.']
                ]
            ],
            [
                'title' => 'ЭЛЕКТРОМОНТАЖНЫЕ РАБОТЫ',
                'items' => [
                    ['name' => 'Установка розеток', 'unit' => 'шт'],
                    ['name' => 'Прокладка кабеля', 'unit' => 'м.п.'],
                    ['name' => 'Монтаж светильников', 'unit' => 'шт']
                ]
            ],
            [
                'title' => 'САНТЕХНИЧЕСКИЕ РАБОТЫ',
                'items' => [
                    ['name' => 'Установка смесителя', 'unit' => 'шт'],
                    ['name' => 'Монтаж унитаза', 'unit' => 'шт'],
                    ['name' => 'Подключение стиральной машины', 'unit' => 'шт']
                ]
            ]
        ];
        
        // Формируем общий список работ для поиска
        $works = [];
        foreach ($sections as $section) {
            foreach ($section['items'] as $item) {
                $item['section'] = $section['title'];
                $works[] = $item;
            }
        }
        
        // Добавляем дополнительные популярные работы
        $additionalWorks = [
            ['name' => 'Монтаж натяжных потолков', 'unit' => 'м2', 'section' => 'ПОТОЛОЧНЫЕ РАБОТЫ'],
            ['name' => 'Устройство стяжки пола', 'unit' => 'м2', 'section' => 'НАПОЛЬНЫЕ РАБОТЫ'],
            ['name' => 'Установка межкомнатных дверей', 'unit' => 'шт', 'section' => 'СТОЛЯРНЫЕ РАБОТЫ'],
            ['name' => 'Установка входной двери', 'unit' => 'шт', 'section' => 'СТОЛЯРНЫЕ РАБОТЫ'],
            ['name' => 'Штукатурка стен', 'unit' => 'м2', 'section' => 'ОТДЕЛОЧНЫЕ РАБОТЫ'],
            ['name' => 'Устройство перегородок из ГКЛ', 'unit' => 'м2', 'section' => 'КОНСТРУКЦИИ'],
            ['name' => 'Утепление стен', 'unit' => 'м2', 'section' => 'ТЕПЛОИЗОЛЯЦИОННЫЕ РАБОТЫ'],
            ['name' => 'Монтаж электрощита', 'unit' => 'шт', 'section' => 'ЭЛЕКТРОМОНТАЖНЫЕ РАБОТЫ']
        ];
        
        $works = array_merge($works, $additionalWorks);
        
        return [
            'sections' => $sections,
            'works' => $works
        ];
    }
}
