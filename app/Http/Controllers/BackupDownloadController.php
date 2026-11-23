<?php

namespace App\Http\Controllers;

use App\Services\CrmBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupDownloadController extends Controller
{
    public function __invoke(Request $request, string $filename, CrmBackupService $backups): BinaryFileResponse
    {
        Gate::authorize('system.backup');

        $path = $backups->resolveDownloadPath($filename);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/zip',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}