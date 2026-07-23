<?php

namespace App\Services;

use App\Mail\ScheduledReportMail;
use App\Models\CustomReport;
use App\Models\ScheduledReport;
use App\Models\User;
use App\Repositories\Contracts\ScheduledReportRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

/**
 * Scheduled Reports — deliberately scoped to saved Custom Reports
 * only (a required `custom_report_id`), not arbitrary built-in
 * reports: Custom Reports all share one real, generic tabular shape
 * (columns + rows), which is what makes scheduled CSV/PDF generation
 * genuinely correct for any of them. Built-in reports like the Income
 * Statement or Cash Flow have their own bespoke shapes (sections,
 * nested breakdowns) that a generic export can't represent honestly —
 * see the sprint doc's explicit scope note. Build the report you want
 * emailed as a Custom Report first, then schedule it.
 *
 * `process()` is the real logic a console command (or, in a real
 * deployment, Laravel's scheduler) calls on each tick — this project's
 * standing sandbox limitation (composer install blocked, so the
 * application layer never actually executes here) means this has
 * never run end-to-end in this environment, the same caveat every
 * other piece of real, unexecuted application code in this project
 * carries.
 */
class ScheduledReportService
{
    public function __construct(
        private readonly ScheduledReportRepositoryInterface $scheduledReports,
        private readonly CustomReportService $customReports,
        private readonly ReportExportService $export,
    ) {}

    public function create(User $actor, array $data): ScheduledReport
    {
        $customReport = CustomReport::where('tenant_id', $actor->tenant_id)->findOrFail($data['custom_report_id']);

        return $this->scheduledReports->create(array_merge($data, [
            'tenant_id' => $actor->tenant_id,
            'report_key' => $customReport->name,
            'created_by_user_id' => $actor->id,
            'next_run_at' => $this->computeNextRunAt($data['frequency']),
        ]));
    }

    public function update(ScheduledReport $schedule, array $data): ScheduledReport
    {
        if (isset($data['frequency']) && $data['frequency'] !== $schedule->frequency) {
            $data['next_run_at'] = $this->computeNextRunAt($data['frequency']);
        }

        return $this->scheduledReports->update($schedule, $data);
    }

    public function computeNextRunAt(string $frequency): Carbon
    {
        return match ($frequency) {
            ScheduledReport::FREQUENCY_DAILY => now()->addDay(),
            ScheduledReport::FREQUENCY_WEEKLY => now()->addWeek(),
            ScheduledReport::FREQUENCY_MONTHLY => now()->addMonthNoOverflow(),
            default => throw new InvalidArgumentException("Unknown frequency '{$frequency}'."),
        };
    }

    /**
     * Runs every due, active schedule for the current tenant context:
     * executes the saved Custom Report, generates the configured
     * format (CSV or PDF), emails it to every recipient, and rolls
     * `next_run_at` forward from the frequency. A failure on one
     * schedule (e.g. its Custom Report was deleted) doesn't stop the
     * rest — logged and skipped, not fatal to the whole run.
     */
    public function process(): array
    {
        $due = ScheduledReport::where('is_active', true)->where('next_run_at', '<=', now())->with('customReport')->get();
        $results = ['sent' => 0, 'skipped' => 0];

        foreach ($due as $schedule) {
            if (! $schedule->customReport) {
                $results['skipped']++;

                continue;
            }

            $rows = $this->customReports->run($schedule->customReport);
            $columns = $schedule->customReport->group_by ? [$schedule->customReport->group_by, 'total'] : $schedule->customReport->columns;

            $fileBytes = $schedule->format === 'pdf'
                ? $this->export->toPdf($schedule->customReport->name, $columns, $rows)
                : $this->export->toCsv($columns, $rows);
            $fileName = str($schedule->customReport->name)->slug().'.'.$schedule->format;
            $mime = $schedule->format === 'pdf' ? 'application/pdf' : 'text/csv';

            foreach ($schedule->recipients as $recipient) {
                Mail::to($recipient)->send(new ScheduledReportMail($schedule->customReport->name, $fileBytes, $fileName, $mime));
            }

            $schedule->update(['last_run_at' => now(), 'next_run_at' => $this->computeNextRunAt($schedule->frequency)]);
            $results['sent']++;
        }

        return $results;
    }
}
