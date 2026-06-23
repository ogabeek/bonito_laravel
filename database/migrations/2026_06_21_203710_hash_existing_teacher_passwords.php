<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $activityConnection = config('activitylog.database_connection');
        $activityTable = config('activitylog.table_name', 'activity_log');

        if (! Schema::connection($activityConnection)->hasTable($activityTable)) {
            return;
        }

        DB::connection($activityConnection)
            ->table($activityTable)
            ->where('subject_type', App\Models\Teacher::class)
            ->select(['id', 'properties'])
            ->orderBy('id')
            ->chunkById(100, function ($activities) use ($activityConnection, $activityTable): void {
                foreach ($activities as $activity) {
                    $properties = json_decode($activity->properties ?? '', true);

                    if (! is_array($properties)) {
                        continue;
                    }

                    unset($properties['changes']['password'], $properties['original']['password']);

                    DB::connection($activityConnection)
                        ->table($activityTable)
                        ->where('id', $activity->id)
                        ->update(['properties' => json_encode($properties, JSON_THROW_ON_ERROR)]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
