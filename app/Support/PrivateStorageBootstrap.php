<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class PrivateStorageBootstrap
{
    public static function ensureRootExists(): void
    {
        $root = (string) config('filesystems.disks.local.root');

        if ($root === '') {
            return;
        }

        if (! File::isDirectory($root)) {
            File::makeDirectory($root, 0755, true, true);
        }

        $attachments = $root.DIRECTORY_SEPARATOR.'attachments';

        if (! File::isDirectory($attachments)) {
            File::makeDirectory($attachments, 0755, true, true);
        }
    }
}