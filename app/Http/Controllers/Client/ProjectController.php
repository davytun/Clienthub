<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Project;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    private function client()
    {
        return auth()->guard('client')->user();
    }

    public function index()
    {
        $projects = Project::where('client_id', $this->client()->id)
            ->latest()
            ->get();

        return view('client.projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        // Global scope already filtered by business_id.
        // Also enforce this client can only see their own projects.
        abort_unless($project->client_id === $this->client()->id, 403);

        $project->load(['files.uploader', 'messages.sender']);

        // Mark all staff-sent messages on this project as read for the client
        $project->messages()
            ->where('sender_id', '!=', $this->client()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('client.projects.show', compact('project'));
    }

    /**
     * Client downloads a file from their own project.
     */
    public function downloadFile(Project $project, File $file): StreamedResponse
    {
        abort_unless($project->client_id === $this->client()->id, 403);
        abort_unless($file->project_id === $project->id, 403);
        abort_unless(Storage::exists($file->path), 404);

        return Storage::download($file->path, $file->original_name);
    }
}
