<?php

namespace App\Http\Controllers;

use App\Enums\AttachmentVisibility;
use App\Models\RequestAttachment;
use App\Services\AuditLogService;
use App\Services\CustomerAccessService;
use App\Support\PersistentStoragePath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(private AuditLogService $audit, private CustomerAccessService $access) {}

    public function staff(Request $request, RequestAttachment $attachment): BinaryFileResponse|StreamedResponse
    {
        Gate::authorize('view', $attachment->serviceRequest);

        if ($attachment->visibility === AttachmentVisibility::Internal) {
            $this->audit->record('attachment.downloaded', $attachment, 'success', [
                'request_id' => $attachment->service_request_id,
            ], $request->user());
        }

        return $this->download($attachment);
    }

    public function customer(Request $request, RequestAttachment $attachment): BinaryFileResponse|StreamedResponse
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

    private function download(RequestAttachment $attachment): BinaryFileResponse|StreamedResponse
    {
        abort_if(str_contains($attachment->storage_path, '..') || str_starts_with($attachment->storage_path, '/'), 404);

        $headers = [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'attachment; filename="'.$attachment->original_name.'"',
        ];

        if (Storage::disk('local')->exists($attachment->storage_path)) {
            return Storage::disk('local')->download($attachment->storage_path, $attachment->original_name, $headers);
        }

        $legacy = PersistentStoragePath::existingFilePath(
            (string) config('filesystems.disks.local.root'),
            $attachment->storage_path,
        );

        if ($legacy !== null) {
            return response()->download($legacy, $attachment->original_name, $headers);
        }

        Log::warning('attachment.missing_on_disk', [
            'attachment_id' => $attachment->id,
            'service_request_id' => $attachment->service_request_id,
            'storage_path' => $attachment->storage_path,
            'disk_root' => config('filesystems.disks.local.root'),
        ]);

        abort(404);
    }
}
