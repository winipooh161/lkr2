<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProjectFileController extends Controller
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
     * Download a project file.
     *
     * @param  \App\Models\ProjectFile  $file
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(ProjectFile $file)
    {
        // Проверяем, что файл принадлежит объекту клиента
        $project = Project::findOrFail($file->project_id);
        
        // Администратор имеет доступ ко всем файлам
        $user = User::find(auth()->id());
        if (!$user->isAdmin() && $project->phone !== $user->phone) {
            abort(403, 'У вас нет доступа к этому файлу.');
        }
        
        $path = 'project_files/' . $file->project_id . '/' . $file->filename;
        
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Файл не найден.');
        }
        
        return response()->download(storage_path('app/public/' . $path), $file->original_name);
    }
}
