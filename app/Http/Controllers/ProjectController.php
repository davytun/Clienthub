<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::with(['client'])
            ->latest()
            ->paginate(20);

        return view('projects.index', compact('projects'));
    }

    public function create(): View
    {
        $this->authorize('create', Project::class);

        $clients = User::where('business_id', auth()->user()->business_id)
            ->where('role', 'client')
            ->orderBy('name')
            ->get();

        return view('projects.create', compact('clients'));
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        // Verify the selected client belongs to this business
        $client = User::where('id', $request->client_id)
            ->where('business_id', auth()->user()->business_id)
            ->where('role', 'client')
            ->firstOrFail();

        $project = Project::create([
            'business_id' => auth()->user()->business_id,
            'client_id'   => $client->id,
            'title'       => $request->title,
            'description' => $request->description,
            'status'      => $request->status,
        ]);

        ActivityLog::record('project.created', $project, ['title' => $project->title]);

        return redirect()->route('projects.index')->with('success', 'Project created.');
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $project->load(['client', 'files.uploader', 'messages.sender']);

        // Mark all client-sent messages on this project as read for staff
        $project->messages()
            ->whereHas('sender', fn ($q) => $q->where('role', 'client'))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('projects.show', compact('project'));
    }

    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        $clients = User::where('business_id', auth()->user()->business_id)
            ->where('role', 'client')
            ->orderBy('name')
            ->get();

        return view('projects.edit', compact('project', 'clients'));
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $client = User::where('id', $request->client_id)
            ->where('business_id', auth()->user()->business_id)
            ->where('role', 'client')
            ->firstOrFail();

        $project->update([
            'client_id'   => $client->id,
            'title'       => $request->title,
            'description' => $request->description,
            'status'      => $request->status,
        ]);

        ActivityLog::record('project.updated', $project, ['title' => $project->title]);

        return redirect()->route('projects.show', $project)->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        ActivityLog::record('project.deleted', $project, ['title' => $project->title]);
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }
}
