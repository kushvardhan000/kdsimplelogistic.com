<?php

namespace App\Observers;

use App\Models\LogsheetImport;
use Illuminate\Support\Facades\Storage;

class LogsheetImportObserver
{
    public function deleting(LogsheetImport $import): void
    {
        if ($import->file_path && Storage::disk('public')->exists($import->file_path)) {
            Storage::disk('public')->delete($import->file_path);
        }
    }
}