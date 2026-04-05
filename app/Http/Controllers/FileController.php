<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * Store a newly uploaded file against a project.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('uploadFile', $project);

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB
                'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
            ],
        ]);

        $uploaded = $request->file('file');
        $extension = $uploaded->getClientOriginalExtension();
        $storagePath = "files/{$project->business_id}/{$project->id}/" . Str::uuid() . ".{$extension}";

        // Store on the default local disk (private — not publicly accessible)
        Storage::put($storagePath, file_get_contents($uploaded->getRealPath()));

        File::create([
            'project_id'    => $project->id,
            'business_id'   => $project->business_id,
            'uploaded_by'   => auth()->id(),
            'original_name' => $uploaded->getClientOriginalName(),
            'path'          => $storagePath,
            'size_bytes'    => $uploaded->getSize(),
        ]);

        return back()->with('success', 'File uploaded.');
    }

    /**
     * Stream a file download for authenticated staff.
     */
    public function download(File $file): StreamedResponse
    {
        $this->authorize('download', $file);

        abort_unless(Storage::exists($file->path), 404);

        return Storage::download($file->path, $file->original_name);
    }

    /**
     * Delete a file record and its stored file.
     */
    public function destroy(File $file): RedirectResponse
    {
        $this->authorize('delete', $file);

        Storage::delete($file->path);
        $file->delete();

        return back()->with('success', 'File deleted.');
    }
}
