<?php

namespace App\Http\Controllers;

use App\Enums\AttachmentVisibility;
use App\Models\RequestAttachment;
use App\Services\AuditLogService;
use App\Services\CustomerAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(private AuditLogService $audit, private CustomerAccessService $access) {}

    public function staff(Request $request, RequestAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment->serviceRequest);

        if ($attachment->visibility === AttachmentVisibility::Internal) {
            $this->audit->record('attachment.downloaded', $attachment, 'success', [
                'request_id' => $attachment->service_request_id,
            ], $request->user());
        }

        return $this->download($attachment);
    }

    public function customer(Request $request, RequestAttachment $attachment): StreamedResponse
    {
        $serviceRequest = $this->access->resolve($request);

        abort_unless(
            $serviceRequest !== null
            && $serviceRequest->id === $attachment->service_request_id
            && $attachment->visibility === AttachmentVisibility::CustomerVisible,
            404,
        );

        return $this->download($attachment);
    }

    private function download(RequestAttachment $attachment): StreamedResponse
    {
        abort_if(str_contains($attachment->storage_path, '..') || str_starts_with($attachment->storage_path, '/'), 404);
        abort_unless(Storage::disk('local')->exists($attachment->storage_path), 404);

        return Storage::disk('local')->download($attachment->storage_path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'attachment; filename="'.$attachment->original_name.'"',
        ]);
    }
}
