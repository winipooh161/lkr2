<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\ProjectFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class EstimateClientPdfService
{
    /**
     * Создать или обновить PDF смету для клиента
     *
     * @param Estimate $estimate
     * @return bool
     */
    public function createOrUpdateClientPdf(Estimate $estimate): bool
    {
        try {
            Log::info('Создание PDF сметы для клиента', [
                'estimate_id' => $estimate->id,
                'project_id' => $estimate->project_id
            ]);

            // Проверяем, что у сметы есть связанный проект
            if (!$estimate->project) {
                Log::warning('У сметы нет связанного проекта', ['estimate_id' => $estimate->id]);
                return false;
            }

            // Генерируем HTML для PDF
            $html = $this->generateEstimateHtml($estimate);
            
            // Генерируем PDF
            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                    'dpi' => 150,
                    'defaultMediaType' => 'screen',
                    'isFontSubsettingEnabled' => true
                ]);

            // Определяем имя файла
            $fileName = $this->generateFileName($estimate);
            $filePath = "project_files/{$estimate->project_id}/{$fileName}";

            // Сохраняем PDF файл
            $pdfContent = $pdf->output();
            Storage::disk('public')->put($filePath, $pdfContent);

            // Проверяем, есть ли уже файл сметы для этого проекта
            $existingFile = ProjectFile::where('project_id', $estimate->project_id)
                ->where('document_type', 'estimate')
                ->where('description', 'like', '%смета%')
                ->first();

            if ($existingFile) {
                // Обновляем существующий файл
                $this->updateExistingFile($existingFile, $filePath, $fileName, $pdfContent);
                Log::info('Обновлен существующий файл PDF сметы', [
                    'file_id' => $existingFile->id,
                    'estimate_id' => $estimate->id
                ]);
            } else {
                // Создаем новый файл
                $this->createNewFile($estimate, $filePath, $fileName, $pdfContent);
                Log::info('Создан новый файл PDF сметы', [
                    'estimate_id' => $estimate->id
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Ошибка при создании PDF сметы для клиента', [
                'estimate_id' => $estimate->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Генерировать HTML для PDF сметы
     *
     * @param Estimate $estimate
     * @return string
     */
    private function generateEstimateHtml(Estimate $estimate): string
    {
        $project = $estimate->project;
        $jsonData = $estimate->json_data ?? [];
        
        // Получаем данные сметы
        $sections = $this->processEstimateData($jsonData);
        $totals = $this->calculateTotals($jsonData);

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Смета работ</title>
            <style>
                @page {
                    margin: 20mm;
                    size: A4;
                }
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: "DejaVu Sans", sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #333;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .header h1 {
                    font-size: 18px;
                    margin-bottom: 5px;
                }
                .project-info {
                    margin-bottom: 20px;
                }
                .project-info table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .project-info td {
                    padding: 3px 5px;
                    border: 1px solid #ddd;
                }
                .estimate-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                .estimate-table th,
                .estimate-table td {
                    border: 1px solid #333;
                    padding: 8px 5px;
                    text-align: left;
                    vertical-align: middle;
                }
                .estimate-table th {
                    background-color: #f5f5f5;
                    font-weight: bold;
                    text-align: center;
                }
                .section-header {
                    background-color: #e8f4f8 !important;
                    font-weight: bold;
                    text-align: center !important;
                }
                .text-right {
                    text-align: right !important;
                }
                .text-center {
                    text-align: center !important;
                }
                .total-row {
                    background-color: #f9f9f9;
                    font-weight: bold;
                }
                .grand-total {
                    background-color: #e6f3ff;
                    font-weight: bold;
                    font-size: 14px;
                }
                .footer {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                }
                .signature-block {
                    margin-top: 40px;
                }
                .signature-line {
                    border-bottom: 1px solid #333;
                    width: 200px;
                    display: inline-block;
                    margin: 0 10px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>СМЕТА РАБОТ</h1>
                <p>' . htmlspecialchars($estimate->name) . '</p>
            </div>

            <div class="project-info">
                <table>
                    <tr>
                        <td style="width: 20%;"><strong>Заказчик:</strong></td>
                        <td style="width: 30%;">' . htmlspecialchars($project->client_name) . '</td>
                        <td style="width: 20%;"><strong>Объект:</strong></td>
                        <td style="width: 30%;">' . htmlspecialchars($project->address) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Телефон:</strong></td>
                        <td>' . htmlspecialchars($project->phone) . '</td>
                        <td><strong>Дата:</strong></td>
                        <td>' . now()->format('d.m.Y') . '</td>
                    </tr>
                </table>
            </div>

            <table class="estimate-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">№</th>
                        <th style="width: 45%;">Наименование работ</th>
                        <th style="width: 10%;">Ед. изм.</th>
                        <th style="width: 10%;">Кол-во</th>
                        <th style="width: 15%;">Цена, руб.</th>
                        <th style="width: 15%;">Сумма, руб.</th>
                    </tr>
                </thead>
                <tbody>';

        $rowNumber = 1;
        foreach ($sections as $section) {
            if ($section['type'] === 'header') {
                $html .= '<tr class="section-header">
                    <td colspan="6">' . htmlspecialchars($section['name']) . '</td>
                </tr>';
            } else {
                $html .= '<tr>
                    <td class="text-center">' . $rowNumber . '</td>
                    <td>' . htmlspecialchars($section['name']) . '</td>
                    <td class="text-center">' . htmlspecialchars($section['unit'] ?? '') . '</td>
                    <td class="text-center">' . number_format($section['quantity'] ?? 0, 2, ',', ' ') . '</td>
                    <td class="text-right">' . number_format($section['price'] ?? 0, 2, ',', ' ') . '</td>
                    <td class="text-right">' . number_format($section['sum'] ?? 0, 2, ',', ' ') . '</td>
                </tr>';
                $rowNumber++;
            }
        }

        $html .= '</tbody>
            </table>

            <table class="estimate-table">
                <tr class="grand-total">
                    <td colspan="5" class="text-right"><strong>ИТОГО:</strong></td>
                    <td class="text-right"><strong>' . number_format($totals['total'], 2, ',', ' ') . ' руб.</strong></td>
                </tr>
            </table>

            <div class="footer">
                <p><strong>Примечания:</strong></p>
                <p>• Цены указаны с учетом всех материалов и работ</p>
                <p>• Срок выполнения работ согласовывается дополнительно</p>
                <p>• Гарантия на выполненные работы - 12 месяцев</p>
            </div>

            <div class="signature-block">
                <p>Исполнитель: <span class="signature-line"></span> _______________</p>
                <br>
                <p>Заказчик: <span class="signature-line"></span> _______________</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Обработать данные сметы из JSON
     */
    private function processEstimateData(array $jsonData): array
    {
        $sections = [];
        
        if (isset($jsonData['sheets']) && is_array($jsonData['sheets'])) {
            foreach ($jsonData['sheets'] as $sheet) {
                if (isset($sheet['data']) && is_array($sheet['data'])) {
                    foreach ($sheet['data'] as $row) {
                        if (!empty($row['name'])) {
                            $sections[] = [
                                'type' => $row['_type'] ?? 'item',
                                'name' => $row['name'],
                                'unit' => $row['unit'] ?? '',
                                'quantity' => $row['quantity'] ?? 0,
                                'price' => $row['price'] ?? 0,
                                'sum' => $row['_result_sum'] ?? ($row['sum'] ?? 0)
                            ];
                        }
                    }
                }
            }
        }
        
        return $sections;
    }

    /**
     * Рассчитать итоги
     */
    private function calculateTotals(array $jsonData): array
    {
        $total = 0;
        
        // Попробуем взять из объекта totals
        if (isset($jsonData['totals']['client_grand_total'])) {
            $total = (float) $jsonData['totals']['client_grand_total'];
        } elseif (isset($jsonData['totals']['grand_total'])) {
            $total = (float) $jsonData['totals']['grand_total'];
        } else {
            // Считаем вручную
            if (isset($jsonData['sheets'])) {
                foreach ($jsonData['sheets'] as $sheet) {
                    if (isset($sheet['data'])) {
                        foreach ($sheet['data'] as $row) {
                            if (!isset($row['_type']) || $row['_type'] !== 'header') {
                                $sum = $row['_result_sum'] ?? ($row['sum'] ?? 0);
                                if (is_numeric($sum)) {
                                    $total += (float) $sum;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return ['total' => $total];
    }

    /**
     * Генерировать имя файла
     */
    private function generateFileName(Estimate $estimate): string
    {
        $estimateType = '';
        switch ($estimate->type) {
            case 'materials':
                $estimateType = 'материалы';
                break;
            case 'additional':
                $estimateType = 'доп.работы';
                break;
            default:
                $estimateType = 'работы';
        }
        
        return "smeta_{$estimateType}_{$estimate->id}_" . date('Y-m-d') . '.pdf';
    }

    /**
     * Обновить существующий файл
     */
    private function updateExistingFile(ProjectFile $file, string $filePath, string $fileName, string $content): void
    {
        // Удаляем старый файл, если он существует
        if (Storage::disk('public')->exists("project_files/{$file->project_id}/{$file->filename}")) {
            Storage::disk('public')->delete("project_files/{$file->project_id}/{$file->filename}");
        }

        // Обновляем запись в базе данных
        $file->update([
            'filename' => $fileName,
            'original_name' => $fileName,
            'size' => strlen($content),
            'mime_type' => 'application/pdf',
            'description' => 'Смета работ (автоматически обновлено ' . now()->format('d.m.Y H:i') . ')',
        ]);
    }

    /**
     * Создать новый файл
     */
    private function createNewFile(Estimate $estimate, string $filePath, string $fileName, string $content): void
    {
        ProjectFile::create([
            'project_id' => $estimate->project_id,
            'filename' => $fileName,
            'original_name' => $fileName,
            'file_type' => 'document',
            'document_type' => 'estimate',
            'size' => strlen($content),
            'mime_type' => 'application/pdf',
            'description' => 'Смета работ (автоматически создано ' . now()->format('d.m.Y H:i') . ') estimate_id:' . $estimate->id,
        ]);
    }
}
