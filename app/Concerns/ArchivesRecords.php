<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

/**
 * Shared soft-delete archive/restore plumbing for admin CRUD controllers.
 * The consuming controller must also use {@see LogsActivityActions}.
 *
 * The activity action name is derived from the model ("student_archived",
 * "teacher_restored", …) so the audit log keeps its existing entries. The
 * withTrashed() lookup + restore() stay in the controller because they need
 * the concrete model type (route binding can't resolve trashed models).
 */
trait ArchivesRecords
{
    /** Soft-delete a record, log it, and return to the dashboard. */
    protected function archiveRecord(Model $record, string $label): RedirectResponse
    {
        $record->delete();

        return $this->dashboardAfter($record, $label, 'archived');
    }

    /** Log a just-restored record and return to the dashboard. */
    protected function restoredRecord(Model $record, string $label): RedirectResponse
    {
        return $this->dashboardAfter($record, $label, 'restored');
    }

    private function dashboardAfter(Model $record, string $label, string $verb): RedirectResponse
    {
        $this->logActivity($record, Str::snake(class_basename($record))."_{$verb}");

        return redirect()->route('admin.dashboard')->with('success', "{$label} {$verb} successfully!");
    }
}
