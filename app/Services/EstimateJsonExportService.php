<?php

namespace App\Services;

use App\Models\Estimate;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Barryvdh\DomPDF\Facade\Pdf;

class EstimateJsonExportService
{
    protected $jsonTemplateService;

    public function __construct(EstimateJsonTemplateService $jsonTemplateService)
    {
        $this->jsonTemplateService = $jsonTemplateService;
    }

    /**
     * Экспорт сметы в Excel на основе JSON данных
     *
     * @param Estimate $estimate
     * @param string|null $version Версия экспорта (null - полная, 'client' - для заказчика, 'contractor' - для мастера)
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportToExcel(Estimate $estimate, ?string $version = null)
    {
        try {
            Log::info("Начинаем экспорт сметы #{$estimate->id} в Excel", ['version' => $version]);
            
            // Загружаем JSON данные
            $jsonData = $this->jsonTemplateService->loadEstimateData($estimate);
            
            if (!$jsonData) {
                Log::error("Не удалось загрузить JSON данные для сметы #{$estimate->id}");
                
                // Создаем базовые данные, если их нет
                $jsonData = $this->createEmptyEstimateData($estimate);
                Log::info("Созданы базовые данные для экспорта");
            }

            Log::info("JSON данные загружены", [
                'has_sheets' => isset($jsonData['sheets']) && !empty($jsonData['sheets']),
                'has_data' => isset($jsonData['sheets'][0]['data']) && !empty($jsonData['sheets'][0]['data']),
                'has_totals' => isset($jsonData['totals']) && !empty($jsonData['totals']),
                'data_count' => isset($jsonData['sheets'][0]['data']) ? count($jsonData['sheets'][0]['data']) : 0
            ]);

            // Создаем новую книгу Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Настраиваем основную информацию
            $this->setupSheetHeader($sheet, $estimate, $jsonData);
            
            // Добавляем данные в зависимости от версии
            $this->addDataToSheet($sheet, $jsonData, $version);
            
            // Применяем стили
            $this->applyStyles($sheet, $jsonData, $version);
            
            // Настраиваем ширину колонок
            $this->adjustColumnWidths($sheet);

            // Создаем файл и отдаем на загрузку
            $fileName = $this->generateFileName($estimate, $version, 'xlsx');
            
            Log::info("Экспорт Excel завершен успешно", ['filename' => $fileName]);
            
            $writer = new Xlsx($spreadsheet);
            
            return response()->streamDownload(
                function() use ($writer) {
                    $writer->save('php://output');
                },
                $fileName,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            );

        } catch (\Exception $e) {
            Log::error('Ошибка экспорта в Excel: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'version' => $version,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Экспорт сметы в PDF на основе JSON данных
     *
     * @param Estimate $estimate
     * @param string|null $version Версия экспорта
     * @return \Illuminate\Http\Response
     */
    public function exportToPdf(Estimate $estimate, ?string $version = null)
    {
        try {
            Log::info("Начинаем экспорт сметы #{$estimate->id} в PDF", ['version' => $version]);
            
            // Загружаем JSON данные
            $jsonData = $this->jsonTemplateService->loadEstimateData($estimate);
            
            if (!$jsonData) {
                Log::error("Не удалось загрузить JSON данные для сметы #{$estimate->id}");
                
                // Создаем базовые данные, если их нет
                $jsonData = $this->createEmptyEstimateData($estimate);
                Log::info("Созданы базовые данные для PDF экспорта");
            }

            Log::info("JSON данные для PDF загружены", [
                'has_sheets' => isset($jsonData['sheets']) && !empty($jsonData['sheets']),
                'has_data' => isset($jsonData['sheets'][0]['data']) && !empty($jsonData['sheets'][0]['data']),
                'has_totals' => isset($jsonData['totals']) && !empty($jsonData['totals']),
                'data_count' => isset($jsonData['sheets'][0]['data']) ? count($jsonData['sheets'][0]['data']) : 0
            ]);

            // Формируем HTML для PDF
            $html = $this->generateHtmlForPdf($estimate, $jsonData, $version);
            
            // Создаем PDF
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'landscape');
            
            // Настройки для корректного отображения русского текста
            $pdf->getDomPDF()->set_option('defaultFont', 'DejaVu Sans');
            $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
            $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);

            $fileName = $this->generateFileName($estimate, $version, 'pdf');
            
            Log::info("Экспорт PDF завершен успешно", ['filename' => $fileName]);
            
            return $pdf->download($fileName);

        } catch (\Exception $e) {
            Log::error('Ошибка экспорта в PDF: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'version' => $version,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Настройка заголовка листа Excel
     */
    protected function setupSheetHeader($sheet, Estimate $estimate, array $jsonData)
    {
        // Название сметы
        $sheet->setCellValue('A1', $estimate->name);
        $sheet->mergeCells('A1:K1');
        
        // Информация о проекте
        if ($estimate->project) {
            $sheet->setCellValue('A2', 'Объект: ' . $estimate->project->address);
            $sheet->mergeCells('A2:K2');
        }
        
        // Дата создания
        $sheet->setCellValue('A3', 'Дата: ' . now()->format('d.m.Y'));
        $sheet->mergeCells('A3:K3');
        
        // Пустая строка
        $sheet->setCellValue('A4', '');
    }

    /**
     * Добавление данных в лист Excel
     */
    protected function addDataToSheet($sheet, array $jsonData, ?string $version)
    {
        $currentRow = 5;
        
        // Заголовки колонок
        $headers = $this->getHeaders($version);
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . $currentRow, $header);
            $column++;
        }
        $currentRow++;

        // Получаем данные из разделов (sections) - основной источник данных
        $sectionsData = $jsonData['sections'] ?? [];
        
        // Если sections пустой, пытаемся получить данные из sheets для обратной совместимости
        if (empty($sectionsData) && isset($jsonData['sheets']) && !empty($jsonData['sheets'])) {
            Log::info('Разделы (sections) пусты, используем данные из sheets');
            
            // Получаем данные из первого листа и группируем в разделы
            $sheetsData = $jsonData['sheets'][0]['data'] ?? [];
            $sectionsData = $this->convertSheetsDataToSections($sheetsData);
        }
        
        // Логируем структуру данных для отладки
        Log::info('Структура данных для экспорта:', [
            'total_sections' => count($sectionsData),
            'has_sections' => !empty($sectionsData),
            'has_sheets' => isset($jsonData['sheets']) && !empty($jsonData['sheets']),
            'version' => $version,
            'sections_details' => array_map(function($section) {
                return [
                    'title' => $section['title'] ?? 'Без названия',
                    'items_count' => count($section['items'] ?? []),
                    'items_sample' => array_slice($section['items'] ?? [], 0, 2)
                ];
            }, $sectionsData)
        ]);
        
        if (empty($sectionsData)) {
            // Если данных нет, добавляем заглушку
            Log::warning('Нет разделов для экспорта в смете');
            $sheet->setCellValue('A' . $currentRow, '');
            $sheet->setCellValue('B' . $currentRow, 'Нет данных для отображения');
            $sheet->mergeCells('B' . $currentRow . ':K' . $currentRow);
            $currentRow++;
        } else {
            $itemNumber = 1;
            foreach ($sectionsData as $sectionIndex => $section) {
                // Добавляем заголовок раздела
                $sheet->setCellValue('A' . $currentRow, '');
                $sheet->setCellValue('B' . $currentRow, $section['title'] ?? 'Раздел ' . ($sectionIndex + 1));
                $sheet->mergeCells('B' . $currentRow . ':K' . $currentRow);
                
                // Применяем стиль заголовка раздела
                $range = 'A' . $currentRow . ':K' . $currentRow;
                $sheet->getStyle($range)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => '366092']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                
                $currentRow++;
                
                // Добавляем элементы раздела
                $items = $section['items'] ?? [];
                foreach ($items as $itemIndex => $item) {
                    // Проверяем, что строка содержит данные и не является заголовком
                    if (is_array($item) && !empty($item) && (!isset($item['is_header']) || $item['is_header'] === false)) {
                        $item['number'] = $itemNumber++;
                        $this->addRowToSheet($sheet, $item, $currentRow, $version);
                        $currentRow++;
                    } else {
                        Log::warning("Пропущена строка данных", [
                            'section_index' => $sectionIndex, 
                            'item_index' => $itemIndex, 
                            'is_header' => $item['is_header'] ?? 'не установлено',
                            'item_name' => $item['name'] ?? 'без названия'
                        ]);
                    }
                }
            }
        }

        // Добавляем итоги
        if (isset($jsonData['totals']) && !empty($jsonData['totals'])) {
            $this->addTotalsToSheet($sheet, $jsonData['totals'], $currentRow, $version);
        } else {
            Log::warning('Нет итоговых данных для экспорта');
            // Добавляем заглушку для итогов
            $currentRow += 2;
            $sheet->setCellValue('B' . $currentRow, 'ИТОГО: не рассчитано');
        }
    }

    /**
     * Добавление одной строки данных
     */
    protected function addRowToSheet($sheet, array $row, int $rowNumber, ?string $version)
    {
        // Если это заголовок раздела
        if (isset($row['_type']) && $row['_type'] === 'header') {
            $sheet->setCellValue('A' . $rowNumber, '');
            $sheet->setCellValue('B' . $rowNumber, $row['name'] ?? '');
            $sheet->mergeCells('B' . $rowNumber . ':K' . $rowNumber);
            return;
        }

        // Обычная строка данных
        $sheet->setCellValue('A' . $rowNumber, $row['number'] ?? '');
        $sheet->setCellValue('B' . $rowNumber, $row['name'] ?? '');
        $sheet->setCellValue('C' . $rowNumber, $row['unit'] ?? '');
        
        // Убеждаемся, что количество отображается как число
        $quantity = $row['quantity'] ?? 0;
        $sheet->setCellValueExplicit('D' . $rowNumber, $quantity, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

        if ($version === 'client') {
            // Для клиента показываем клиентские цены
            $clientPrice = $row['client_price'] ?? $row['price'] ?? 0;
            $clientCost = $row['client_cost'] ?? $row['cost'] ?? 0;
            
            $sheet->setCellValueExplicit('E' . $rowNumber, $clientPrice, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('F' . $rowNumber, $clientCost, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            
            // Применяем денежный формат
            $sheet->getStyle('E' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0.00₽');
            $sheet->getStyle('F' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0.00₽');
            
        } elseif ($version === 'contractor') {
            // Для мастера показываем базовые цены
            $price = $row['price'] ?? 0;
            $cost = $row['cost'] ?? 0;
            
            $sheet->setCellValueExplicit('E' . $rowNumber, $price, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('F' . $rowNumber, $cost, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            
            // Применяем денежный формат
            $sheet->getStyle('E' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0.00₽');
            $sheet->getStyle('F' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0.00₽');
            
        } else {
            // Полная версия - показываем все колонки
            $price = $row['price'] ?? 0;
            $cost = $row['cost'] ?? 0;
            $markup = $row['markup'] ?? 0;
            $discount = $row['discount'] ?? 0;
            $clientPrice = $row['client_price'] ?? $price;
            $clientCost = $row['client_cost'] ?? $cost;
            
            $sheet->setCellValueExplicit('E' . $rowNumber, $price, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('F' . $rowNumber, $cost, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('G' . $rowNumber, $markup, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('H' . $rowNumber, $discount, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('I' . $rowNumber, $clientPrice, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('J' . $rowNumber, $clientCost, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            
            // Применяем форматы
            $sheet->getStyle('E' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0.00₽');
            $sheet->getStyle('F' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0.00₽');
            $sheet->getStyle('G' . $rowNumber)->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle('H' . $rowNumber)->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle('I' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0.00₽');
            $sheet->getStyle('J' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0.00₽');
        }
    }

    /**
     * Добавление итогов
     */
    protected function addTotalsToSheet($sheet, array $totals, int $startRow, ?string $version)
    {
        $currentRow = $startRow + 1;
        
        // Пустая строка
        $sheet->setCellValue('A' . $currentRow, '');
        $currentRow++;

        if ($version === 'client') {
            $sheet->setCellValue('B' . $currentRow, 'ИТОГО:');
            $clientTotal = $totals['client_grand_total'] ?? $totals['grand_total'] ?? 0;
            $sheet->setCellValueExplicit('F' . $currentRow, $clientTotal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->getStyle('F' . $currentRow)->getNumberFormat()->setFormatCode('#,##0.00₽');
            
            // Стиль для итоговой строки
            $sheet->getStyle('B' . $currentRow . ':F' . $currentRow)->applyFromArray([
                'font' => ['bold' => true],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THICK],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE]
                ]
            ]);
            
        } elseif ($version === 'contractor') {
            $sheet->setCellValue('B' . $currentRow, 'ИТОГО:');
            $workTotal = $totals['work_total'] ?? $totals['grand_total'] ?? 0;
            $sheet->setCellValueExplicit('F' . $currentRow, $workTotal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->getStyle('F' . $currentRow)->getNumberFormat()->setFormatCode('#,##0.00₽');
            
            // Стиль для итоговой строки
            $sheet->getStyle('B' . $currentRow . ':F' . $currentRow)->applyFromArray([
                'font' => ['bold' => true],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THICK],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE]
                ]
            ]);
            
        } else {
            // Полная версия
            $sheet->setCellValue('B' . $currentRow, 'ИТОГО работы:');
            $workTotal = $totals['work_total'] ?? $totals['grand_total'] ?? 0;
            $sheet->setCellValueExplicit('F' . $currentRow, $workTotal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->getStyle('F' . $currentRow)->getNumberFormat()->setFormatCode('#,##0.00₽');
            
            // Стиль для первой итоговой строки
            $sheet->getStyle('B' . $currentRow . ':F' . $currentRow)->applyFromArray([
                'font' => ['bold' => true],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THICK]
                ]
            ]);
            
            $currentRow++;
            
            $sheet->setCellValue('B' . $currentRow, 'ИТОГО для клиента:');
            $clientTotal = $totals['client_grand_total'] ?? $totals['grand_total'] ?? 0;
            $sheet->setCellValueExplicit('J' . $currentRow, $clientTotal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->getStyle('J' . $currentRow)->getNumberFormat()->setFormatCode('#,##0.00₽');
            
            // Стиль для второй итоговой строки
            $sheet->getStyle('B' . $currentRow . ':J' . $currentRow)->applyFromArray([
                'font' => ['bold' => true],
                'borders' => [
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE]
                ]
            ]);
        }
    }

    /**
     * Получение заголовков колонок в зависимости от версии
     */
    protected function getHeaders(?string $version): array
    {
        $baseHeaders = ['№', 'Наименование работ', 'Ед.изм.', 'Кол-во'];

        if ($version === 'client') {
            return array_merge($baseHeaders, ['Цена', 'Сумма']);
        } elseif ($version === 'contractor') {
            return array_merge($baseHeaders, ['Цена', 'Сумма']);
        } else {
            return array_merge($baseHeaders, [
                'Цена', 'Сумма', 'Наценка', 'Скидка', 'Цена клиента', 'Сумма клиента'
            ]);
        }
    }

    /**
     * Применение стилей к листу Excel
     */
    protected function applyStyles($sheet, array $jsonData, ?string $version)
    {
        // Стиль заголовка
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Стиль заголовков таблицы
        $headerRange = $version === 'client' || $version === 'contractor' ? 'A5:F5' : 'A5:J5';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '2F75B5']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Стили для данных - применяются после построения таблицы
        // Количество колонок зависит от версии
        $maxCol = $version === 'client' || $version === 'contractor' ? 'F' : 'J';
        
        // Подсчитываем количество строк с данными
        $sectionsData = $jsonData['sections'] ?? [];
        $totalDataRows = 0;
        foreach ($sectionsData as $section) {
            $totalDataRows++; // Заголовок раздела
            $items = $section['items'] ?? [];
            foreach ($items as $item) {
                if (is_array($item) && !empty($item) && (!isset($item['is_header']) || $item['is_header'] === false)) {
                    $totalDataRows++; // Строка данных
                }
            }
        }
        
        if ($totalDataRows > 0) {
            $dataRange = 'A6:' . $maxCol . (6 + $totalDataRows - 1);
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ]);
        }
    }

    /**
     * Настройка ширины колонок
     */
    protected function adjustColumnWidths($sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(5);   // №
        $sheet->getColumnDimension('B')->setWidth(40);  // Наименование
        $sheet->getColumnDimension('C')->setWidth(10);  // Ед.изм.
        $sheet->getColumnDimension('D')->setWidth(10);  // Кол-во
        $sheet->getColumnDimension('E')->setWidth(12);  // Цена
        $sheet->getColumnDimension('F')->setWidth(12);  // Сумма
        $sheet->getColumnDimension('G')->setWidth(10);  // Наценка
        $sheet->getColumnDimension('H')->setWidth(10);  // Скидка
        $sheet->getColumnDimension('I')->setWidth(12);  // Цена клиента
        $sheet->getColumnDimension('J')->setWidth(12);  // Сумма клиента
    }

    /**
     * Генерация HTML для PDF
     */
    protected function generateHtmlForPdf(Estimate $estimate, array $jsonData, ?string $version): string
    {
        $styles = '
            <style>
                @font-face {
                    font-family: "DejaVu Sans";
                    src: url("' . storage_path('fonts/dejavu-sans/DejaVuSans.ttf') . '") format("truetype");
                }
                body { 
                    font-family: "DejaVu Sans", Arial, sans-serif; 
                    font-size: 10pt; 
                    line-height: 1.3; 
                    margin: 20px;
                }
                h1 { 
                    text-align: center; 
                    color: #2F75B5; 
                    margin-bottom: 20px; 
                    font-size: 16pt; 
                }
                .estimate-info { 
                    margin-bottom: 15px; 
                }
                table { 
                    border-collapse: collapse; 
                    width: 100%; 
                    margin-bottom: 20px; 
                }
                table, th, td { 
                    border: 1px solid #ddd; 
                }
                th { 
                    background-color: #2F75B5; 
                    color: white; 
                    padding: 8px; 
                    text-align: center; 
                    font-size: 9pt;
                }
                td { 
                    padding: 6px; 
                    text-align: left; 
                    font-size: 9pt;
                }
                tr:nth-child(even) { 
                    background-color: #f9f9f9; 
                }
                .text-right { 
                    text-align: right; 
                }
                .text-center { 
                    text-align: center; 
                }
                .section-header { 
                    background-color: #366092 !important; 
                    color: white; 
                    font-weight: bold; 
                }
                .total-row { 
                    background-color: #BDD7EE; 
                    font-weight: bold; 
                }
            </style>
        ';

        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            ' . $styles . '
            <title>' . htmlspecialchars($estimate->name) . '</title>
        </head>
        <body>
            <h1>' . htmlspecialchars($estimate->name) . '</h1>
            <div class="estimate-info">
                <p><strong>Дата:</strong> ' . now()->format('d.m.Y') . '</p>';
                
        if ($estimate->project) {
            $html .= '<p><strong>Объект:</strong> ' . htmlspecialchars($estimate->project->address) . '</p>';
        }
        
        $html .= '</div><table>';

        // Заголовки
        $headers = $this->getHeaders($version);
        $html .= '<tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr>';

        // Данные из разделов (sections) вместо sheets
        $sectionsData = $jsonData['sections'] ?? [];
        $itemNumber = 1;
        
        if (!empty($sectionsData)) {
            foreach ($sectionsData as $sectionIndex => $section) {
                // Заголовок раздела
                $colspan = ($version === 'client' || $version === 'contractor') ? 6 : 10;
                $html .= '<tr class="section-header">
                    <td colspan="' . $colspan . '">' . htmlspecialchars($section['title'] ?? 'Раздел ' . ($sectionIndex + 1)) . '</td>
                </tr>';
                
                // Элементы раздела
                $items = $section['items'] ?? [];
                foreach ($items as $itemIndex => $item) {
                    if (is_array($item) && !empty($item) && (!isset($item['is_header']) || $item['is_header'] === false)) {
                        $item['number'] = $itemNumber++;
                        $html .= $this->generateHtmlRow($item, $version);
                    }
                }
            }
        } else {
            $colspan = ($version === 'client' || $version === 'contractor') ? 6 : 10;
            $html .= '<tr>
                <td colspan="' . $colspan . '" class="text-center">Нет данных для отображения</td>
            </tr>';
        }

        // Итоги
        if (isset($jsonData['totals'])) {
            $html .= $this->generateTotalsHtml($jsonData['totals'], $version);
        }

        $html .= '</table></body></html>';

        return $html;
    }

    /**
     * Генерация HTML строки для PDF
     */
    protected function generateHtmlRow(array $row, ?string $version): string
    {
        // Заголовок раздела
        if (isset($row['_type']) && $row['_type'] === 'header') {
            $colspan = ($version === 'client' || $version === 'contractor') ? 6 : 10;
            return '<tr class="section-header">
                <td colspan="' . $colspan . '">' . htmlspecialchars($row['name'] ?? '') . '</td>
            </tr>';
        }

        // Обычная строка
        $html = '<tr>';
        $html .= '<td class="text-center">' . htmlspecialchars($row['number'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['name'] ?? '') . '</td>';
        $html .= '<td class="text-center">' . htmlspecialchars($row['unit'] ?? '') . '</td>';
        $html .= '<td class="text-center">' . number_format(floatval($row['quantity'] ?? 0), 0, ',', ' ') . '</td>';

        if ($version === 'client') {
            $clientPrice = floatval($row['client_price'] ?? $row['price'] ?? 0);
            $clientCost = floatval($row['client_cost'] ?? $row['cost'] ?? 0);
            
            $html .= '<td class="text-right">' . number_format($clientPrice, 2, ',', ' ') . ' ₽</td>';
            $html .= '<td class="text-right">' . number_format($clientCost, 2, ',', ' ') . ' ₽</td>';
        } elseif ($version === 'contractor') {
            $price = floatval($row['price'] ?? 0);
            $cost = floatval($row['cost'] ?? 0);
            
            $html .= '<td class="text-right">' . number_format($price, 2, ',', ' ') . ' ₽</td>';
            $html .= '<td class="text-right">' . number_format($cost, 2, ',', ' ') . ' ₽</td>';
        } else {
            $price = floatval($row['price'] ?? 0);
            $cost = floatval($row['cost'] ?? 0);
            $markup = floatval($row['markup'] ?? 0);
            $discount = floatval($row['discount'] ?? 0);
            $clientPrice = floatval($row['client_price'] ?? $price);
            $clientCost = floatval($row['client_cost'] ?? $cost);
            
            $html .= '<td class="text-right">' . number_format($price, 2, ',', ' ') . ' ₽</td>';
            $html .= '<td class="text-right">' . number_format($cost, 2, ',', ' ') . ' ₽</td>';
            $html .= '<td class="text-center">' . number_format($markup, 0) . '%</td>';
            $html .= '<td class="text-center">' . number_format($discount, 0) . '%</td>';
            $html .= '<td class="text-right">' . number_format($clientPrice, 2, ',', ' ') . ' ₽</td>';
            $html .= '<td class="text-right">' . number_format($clientCost, 2, ',', ' ') . ' ₽</td>';
        }

        $html .= '</tr>';
        return $html;
    }

    /**
     * Генерация HTML итогов для PDF
     */
    protected function generateTotalsHtml(array $totals, ?string $version): string
    {
        $html = '<tr><td colspan="10" style="border: none;">&nbsp;</td></tr>';

        if ($version === 'client') {
            $clientTotal = floatval($totals['client_grand_total'] ?? $totals['grand_total'] ?? 0);
            $html .= '<tr class="total-row">
                <td colspan="5" class="text-right"><strong>ИТОГО:</strong></td>
                <td class="text-right"><strong>' . number_format($clientTotal, 2, ',', ' ') . ' ₽</strong></td>
            </tr>';
        } elseif ($version === 'contractor') {
            $workTotal = floatval($totals['work_total'] ?? $totals['grand_total'] ?? 0);
            $html .= '<tr class="total-row">
                <td colspan="5" class="text-right"><strong>ИТОГО:</strong></td>
                <td class="text-right"><strong>' . number_format($workTotal, 2, ',', ' ') . ' ₽</strong></td>
            </tr>';
        } else {
            $workTotal = floatval($totals['work_total'] ?? $totals['grand_total'] ?? 0);
            $clientWorkTotal = floatval($totals['client_work_total'] ?? $totals['client_grand_total'] ?? 0);
            $clientGrandTotal = floatval($totals['client_grand_total'] ?? $totals['grand_total'] ?? 0);
            
            $html .= '<tr class="total-row">
                <td colspan="5" class="text-right"><strong>ИТОГО работы:</strong></td>
                <td class="text-right"><strong>' . number_format($workTotal, 2, ',', ' ') . ' ₽</strong></td>
                <td colspan="3"></td>
                <td class="text-right"><strong>' . number_format($clientWorkTotal, 2, ',', ' ') . ' ₽</strong></td>
            </tr>';
            
            $html .= '<tr class="total-row">
                <td colspan="5" class="text-right"><strong>ОБЩИЙ ИТОГ:</strong></td>
                <td></td>
                <td colspan="3"></td>
                <td class="text-right"><strong>' . number_format($clientGrandTotal, 2, ',', ' ') . ' ₽</strong></td>
            </tr>';
        }

        return $html;
    }

    /**
     * Генерация имени файла
     */
    protected function generateFileName(Estimate $estimate, ?string $version, string $extension): string
    {
        $baseName = 'Смета_' . $estimate->id;
        
        if ($estimate->name) {
            $baseName = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $estimate->name);
            $baseName = trim($baseName);
            if (empty($baseName)) {
                $baseName = 'Смета_' . $estimate->id;
            }
        }

        switch ($version) {
            case 'client':
                $suffix = '_для_заказчика';
                break;
            case 'contractor':
                $suffix = '_для_мастера';
                break;
            default:
                $suffix = '';
        }

        return $baseName . $suffix . '.' . $extension;
    }

    /**
     * Создание базовых данных сметы для экспорта, если JSON данные отсутствуют
     */
    protected function createEmptyEstimateData(Estimate $estimate): array
    {
        return [
            'type' => 'main',
            'version' => '1.0',
            'meta' => [
                'estimate_id' => $estimate->id,
                'estimate_name' => $estimate->name,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ],
            'structure' => [
                'columns' => [
                    ['key' => 'number', 'title' => '№', 'type' => 'text'],
                    ['key' => 'name', 'title' => 'Наименование работ', 'type' => 'text'],
                    ['key' => 'unit', 'title' => 'Ед.изм.', 'type' => 'text'],
                    ['key' => 'quantity', 'title' => 'Кол-во', 'type' => 'number'],
                    ['key' => 'price', 'title' => 'Цена', 'type' => 'currency'],
                    ['key' => 'cost', 'title' => 'Сумма', 'type' => 'currency']
                ]
            ],
            'sections' => [],
            'sheets' => [
                [
                    'name' => 'Основной',
                    'data' => [
                        [
                            'number' => '',
                            'name' => 'Данные сметы отсутствуют или повреждены',
                            'unit' => '',
                            'quantity' => 0,
                            'price' => 0,
                            'cost' => 0,
                            'markup' => 0,
                            'discount' => 0,
                            'client_price' => 0,
                            'client_cost' => 0
                        ]
                    ]
                ]
            ],
            'currentSheet' => 0,
            'totals' => [
                'work_total' => 0,
                'materials_total' => 0,
                'grand_total' => 0,
                'client_work_total' => 0,
                'client_materials_total' => 0,
                'client_grand_total' => 0
            ]
        ];
    }

    /**
     * Конвертация данных из формата sheets в sections для обратной совместимости
     */
    protected function convertSheetsDataToSections(array $sheetsData): array
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
        
        Log::info('Конвертированы данные из sheets в sections', [
            'original_rows' => count($sheetsData),
            'sections_created' => count($sections),
            'total_items' => array_sum(array_map(function($section) { 
                return count($section['items'] ?? []); 
            }, $sections))
        ]);
        
        return $sections;
    }
}
