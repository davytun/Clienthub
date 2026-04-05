<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Mail\NewMessageMail;
use App\Models\Message;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request, Project $project): RedirectResponse
    {
        // Verify this project belongs to the authenticated business
        abort_unless($project->business_id === auth()->user()->business_id, 403);

        $message = Message::create([
            'project_id'  => $project->id,
            'business_id' => $project->business_id,
            'sender_id'   => auth()->id(),
            'body'        => $request->body,
        ]);

        // Notify the client
        $project->load(['client', 'business']);
        Mail::to($project->client->email)
            ->queue(new NewMessageMail($message->load('sender'), $project, recipientIsClient: true));

        return back()->with('success', 'Message sent.');
    }
}
