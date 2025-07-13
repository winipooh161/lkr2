<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProjectFile;
use App\Models\Estimate;

class UpdateEstimatePdfFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estimates:update-pdf-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing estimate PDF files with estimate_id in description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating estimate PDF files...');

        // Найти все файлы типа estimate без estimate_id в описании
        $estimateFiles = ProjectFile::where('document_type', 'estimate')
            ->where('description', 'NOT LIKE', '%estimate_id:%')
            ->get();

        $updated = 0;

        foreach ($estimateFiles as $file) {
            // Найти последнюю смету для этого проекта
            $estimate = Estimate::where('project_id', $file->project_id)
                ->latest()
                ->first();

            if ($estimate) {
                $file->update([
                    'description' => $file->description . ' estimate_id:' . $estimate->id
                ]);
                $updated++;
                $this->line("Updated file {$file->original_name} for project {$file->project_id}");
            }
        }

        $this->info("Updated {$updated} estimate PDF files.");

        return Command::SUCCESS;
    }
}
