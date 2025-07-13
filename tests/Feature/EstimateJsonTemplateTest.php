<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Estimate;
use App\Services\EstimateJsonTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class EstimateJsonTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected $templateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateService = new EstimateJsonTemplateService();
    }

    /** @test */
    public function it_can_load_main_template_from_json()
    {
        // Тест загрузки основного шаблона
        $template = $this->templateService->getTemplateByType('main');
        
        $this->assertIsArray($template);
        $this->assertArrayHasKey('structure', $template);
        $this->assertArrayHasKey('sections', $template);
        $this->assertArrayHasKey('version', $template);
        
        // Проверяем, что есть разделы из реальных данных
        $sectionNames = array_column($template['sections'], 'name');
        $this->assertContains('Демонтажные работы', $sectionNames);
        $this->assertContains('Стены и перегородки', $sectionNames);
    }

    /** @test */
    public function it_can_load_materials_template_from_json()
    {
        // Тест загрузки шаблона материалов
        $template = $this->templateService->getTemplateByType('materials');
        
        $this->assertIsArray($template);
        $this->assertArrayHasKey('structure', $template);
        $this->assertArrayHasKey('sections', $template);
        
        // Проверяем, что есть разделы материалов из реальных данных
        $sectionNames = array_column($template['sections'], 'name');
        $this->assertContains('Общестроительные материалы', $sectionNames);
        $this->assertContains('Электромонтажные материалы', $sectionNames);
    }

    /** @test */
    public function it_can_create_estimate_data_from_template()
    {
        // Создаем тестовые данные
        $user = User::factory()->create(['role' => 'partner']);
        $project = Project::factory()->create(['partner_id' => $user->id]);
        $estimate = Estimate::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'type' => 'main'
        ]);

        // Создаем данные сметы
        $data = $this->templateService->createEstimateData($estimate);
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('sheets', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('structure', $data);
        
        // Проверяем метаданные
        $this->assertEquals($estimate->id, $data['meta']['estimate_id']);
        $this->assertEquals('main', $data['meta']['type']);
        
        // Проверяем, что есть данные в листе
        $this->assertNotEmpty($data['sheets'][0]['data']);
    }

    /** @test */
    public function it_can_save_and_load_estimate_data()
    {
        // Создаем тестовые данные
        $user = User::factory()->create(['role' => 'partner']);
        $project = Project::factory()->create(['partner_id' => $user->id]);
        $estimate = Estimate::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'type' => 'main'
        ]);

        // Создаем и сохраняем данные
        $originalData = $this->templateService->createEstimateData($estimate);
        $saved = $this->templateService->saveEstimateData($estimate, $originalData);
        
        $this->assertTrue($saved);
        
        // Загружаем данные обратно
        $loadedData = $this->templateService->loadEstimateData($estimate);
        
        $this->assertIsArray($loadedData);
        $this->assertEquals($originalData['meta']['estimate_id'], $loadedData['meta']['estimate_id']);
        $this->assertEquals($originalData['meta']['type'], $loadedData['meta']['type']);
    }
}
