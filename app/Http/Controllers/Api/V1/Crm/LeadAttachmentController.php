<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeadAttachmentResource;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadAttachment;
use Illuminate\Http\Request;

class LeadAttachmentController extends Controller
{
    public function index(Lead $lead)
    {
        $this->authorize('view', $lead);

        return LeadAttachmentResource::collection($lead->attachments()->with('uploader')->latest('created_at')->get());
    }

    public function store(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $request->validate(['file' => ['required', 'file', 'max:10240']]); // 10MB

        $uploaded = $request->file('file');
        $path = $uploaded->store("tenants/{$lead->tenant_id}/leads/{$lead->id}/attachments", 'public');

        $attachment = LeadAttachment::create([
            'tenant_id' => $lead->tenant_id,
            'lead_id' => $lead->id,
            'uploaded_by_user_id' => $request->user()->id,
            'original_name' => $uploaded->getClientOriginalName(),
            'storage_path' => $path,
            'mime_type' => $uploaded->getClientMimeType(),
            'size_bytes' => $uploaded->getSize(),
        ]);

        LeadActivity::create([
            'tenant_id' => $lead->tenant_id,
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'type' => LeadActivity::TYPE_ATTACHMENT_ADDED,
            'description' => "Attached {$attachment->original_name}.",
            'created_at' => now(),
        ]);

        return $this->ok(new LeadAttachmentResource($attachment->load('uploader')), 201);
    }

    public function destroy(Request $request, Lead $lead, LeadAttachment $attachment)
    {
        $this->authorize('update', $lead);

        abort_unless($attachment->lead_id === $lead->id, 404);

        $attachment->delete();

        return response()->json(null, 204);
    }
}
