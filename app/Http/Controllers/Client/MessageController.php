<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
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
        $client = auth()->guard('client')->user();

        // Verify this project belongs to this client
        abort_unless($project->client_id === $client->id, 403);

        $message = Message::create([
            'project_id'  => $project->id,
            'business_id' => $project->business_id,
            'sender_id'   => $client->id,
            'body'        => $request->body,
        ]);

        // Notify the business owner
        $project->load(['business.owner', 'client']);
        Mail::to($project->business->owner->email)
            ->queue(new NewMessageMail($message->load('sender'), $project, recipientIsClient: false));

        return back()->with('success', 'Message sent.');
    }
}
