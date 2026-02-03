<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Classlist;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateAssignmentStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assignments:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically update assignment statuses (check for missing assignments, late submissions, etc.)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking assignment statuses...');

        // Use Philippine Time (UTC+08:00)
        $now = Carbon::now('Asia/Manila');

        $updatedCount = 0;
        $missingCount = 0;

        try {
            // Get all assignments with due dates
            $assignments = Assignment::whereNotNull('due_date')
                ->with(['classlist.students' => function ($query) {
                    $query->where('classlist_user.status', 'active');
                }])
                ->get();

            foreach ($assignments as $assignment) {
                // Create deadline from due_date and due_time
                $dueDateTime = $this->getAssignmentDeadline($assignment);

                if (!$dueDateTime) {
                    continue;
                }

                $isPastDue = $now->greaterThan($dueDateTime);

                // Get all active students in this class
                $students = $assignment->classlist->students;

                foreach ($students as $student) {
                    // Check if submission exists
                    $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
                        ->where('user_id', $student->id)
                        ->first();

                    if ($submission) {
                        // Update existing submission status
                        $newStatus = $this->determineSubmissionStatus($submission, $dueDateTime, $now);

                        if ($submission->status !== $newStatus) {
                            $submission->update(['status' => $newStatus]);
                            $updatedCount++;

                            if ($newStatus === 'missing') {
                                $missingCount++;
                            }
                        }
                    } else {
                        // No submission exists
                        if ($isPastDue) {
                            // Create a "missing" submission record
                            AssignmentSubmission::create([
                                'assignment_id' => $assignment->id,
                                'classlist_id' => $assignment->classlist_id,
                                'user_id' => $student->id,
                                'status' => 'missing',
                                'submission_type' => 'file',
                            ]);
                            $updatedCount++;
                            $missingCount++;
                        }
                        // If not past due, student still has time (no record needed yet)
                    }
                }
            }

            $message = "Assignment statuses updated: {$updatedCount} records updated, {$missingCount} marked as missing";
            $this->info($message);

            Log::info('Assignment status update completed', [
                'updated' => $updatedCount,
                'missing' => $missingCount,
                'timestamp' => $now->toDateTimeString(),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $errorMsg = "Error updating assignment statuses: " . $e->getMessage();
            $this->error($errorMsg);
            Log::error($errorMsg, [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Get the assignment deadline as Carbon instance
     */
    private function getAssignmentDeadline(Assignment $assignment): ?Carbon
    {
        if (!$assignment->due_date) {
            return null;
        }

        // Parse dates in Philippine Time (UTC+08:00)
        $dueDate = Carbon::parse($assignment->due_date, 'Asia/Manila');

        // If due_time is set, use it; otherwise default to 23:59:59
        if ($assignment->due_time) {
            $dueTime = Carbon::parse($assignment->due_time, 'Asia/Manila');
            $dueDate->setTime($dueTime->hour, $dueTime->minute, $dueTime->second);
        } else {
            $dueDate->setTime(23, 59, 59);
        }

        return $dueDate;
    }

    /**
     * Determine the correct status for a submission
     */
    private function determineSubmissionStatus(
        AssignmentSubmission $submission,
        Carbon $deadline,
        Carbon $now
    ): string {
        // If already graded and returned, keep that status
        if ($submission->status === 'graded' && $submission->returned_to_student) {
            return 'graded';
        }

        // If graded but not returned, check if it was late
        if ($submission->score !== null) {
            // Check if it was submitted late
            if ($submission->submitted_at && $submission->submitted_at->greaterThan($deadline)) {
                return 'graded'; // Late but graded
            }
            return 'graded';
        }

        // If submitted, determine if on time or late
        if ($submission->submitted_at) {
            if ($submission->submitted_at->greaterThan($deadline)) {
                return 'late'; // Submitted after deadline
            }
            return 'turned_in'; // Submitted on time
        }

        // Not submitted yet
        if ($now->greaterThan($deadline)) {
            return 'missing'; // Past due, not submitted
        }

        return 'assigned'; // Still has time to submit
    }
}
