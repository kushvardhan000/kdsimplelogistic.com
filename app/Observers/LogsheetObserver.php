<?php

namespace App\Observers;

use App\Models\Logsheet;
use App\Models\LogsheetClearing;
use App\Models\LogsheetRawRow;
use Illuminate\Support\Facades\Storage;

class LogsheetObserver
{
    public function deleting(Logsheet $logsheet): void
    {
        LogsheetRawRow::where('log_sheet_no', $logsheet->log_sheet_no)->delete();
        $logsheet->clearings()->delete();
    }

    public function deleted(Logsheet $logsheet): void
    {
        $import = $logsheet->lastImport;
        if ($import && $import->file_path) {
            $remaining = Logsheet::where('last_import_id', $import->id)->count();
            if ($remaining === 0) {
                Storage::disk('public')->delete($import->file_path);
                $import->logsheets()->forceDelete();
                $import->delete();
            }
        }
    }
}
