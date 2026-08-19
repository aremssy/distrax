<?php

namespace App\Jobs;

use App\Models\Export;
use App\Services\ExportManager;
use App\Services\NotificationCenter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Generates a large export off the request cycle. Progress is written to the Export record
 * as rows are processed, and the requesting admin is notified when the file is ready.
 */
class ProcessExport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public function __construct(public Export $export) {}

    public function handle(ExportManager $manager, NotificationCenter $notifications): void
    {
        $manager->process($this->export);

        $fresh = $this->export->refresh();

        if ($fresh->isFailed()) {
            $notifications->toAdmin(
                adminId: $fresh->user_id,
                type: 'system',
                title: 'Export failed',
                body: 'Your '.strtoupper($fresh->format).' export of '.$fresh->resource.' could not be generated.',
                link: route('admin.exports.index'),
            );

            return;
        }

        $notifications->toAdmin(
            adminId: $fresh->user_id,
            type: 'system',
            title: 'Export ready',
            body: 'Your '.strtoupper($fresh->format).' export of '.$fresh->resource.' ('.$fresh->total_rows.' rows) is ready to download.',
            link: route('admin.exports.index'),
        );
    }
}
