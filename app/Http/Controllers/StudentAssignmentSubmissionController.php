<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AssignmentSubmissionAttachment;
use App\Models\Classlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StudentAssignmentSubmissionController extends Controller
{
    protected function ensureEnrolled(Classlist $classlist)
    {
        $enrolled = DB::table('classlist_user')
            ->where('classlist_id', $classlist->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();
        abort_unless($enrolled, 403);
    }

    public function store(Request $request, Classlist $classlist, Assignment $assignment)
    {
        $this->ensureEnrolled($classlist);
        abort_unless($assignment->classlist_id === $classlist->id, 404);

        $data = $request->validate([
            'submission_type' => ['required', 'in:file,link,video_link'],
            'link_url' => ['nullable', 'url', 'max:500', 'required_if:submission_type,link'],
            'video_url' => ['nullable', 'url', 'max:500', 'required_if:submission_type,video_link'],
            'attachments' => ['required_if:submission_type,file', 'array'],
            'attachments.*' => ['file', 'max:51200'], // 50MB each
        ], [
            'attachments.required_if' => 'Please upload at least one file for file submission.',
        ]);

        $submission = AssignmentSubmission::create([
            'classlist_id' => $classlist->id,
            'assignment_id' => $assignment->id,
            'user_id' => Auth::id(),
            'submission_type' => $data['submission_type'],
            'link_url' => $data['link_url'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            // Mark as turned in by default; late/missing/graded are handled by status updater
            'status' => AssignmentSubmission::STATUS_TURNED_IN,
            'submitted_at' => now(),
        ]);

        // Handle file attachments if submission type is file
        if ($data['submission_type'] === 'file') {
            if (!$request->hasFile('attachments')) {
                return redirect()->back()
                    ->withErrors(['attachments' => 'Please upload at least one file for file submission.'])
                    ->withInput();
            }
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('assignment-submissions', 'public');
                AssignmentSubmissionAttachment::create([
                    'assignment_submission_id' => $submission->id,
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getMimeType(),
                    'url' => Storage::disk('public')->url($path),
                    'size' => $file->getSize(),
                    'version' => 1,
                    'is_current' => true,
                    'folder_id' => $request->input('folder_id'),
                ]);
            }
        }

        return redirect()->route('student.assignments.show', [$classlist->id, $assignment->id])
            ->with('success', 'Assignment submitted successfully.');
    }

    public function update(Request $request, Classlist $classlist, Assignment $assignment, AssignmentSubmission $submission)
    {
        $this->ensureEnrolled($classlist);
        abort_unless($assignment->classlist_id === $classlist->id, 404);
        abort_unless($submission->user_id === Auth::id(), 403);
        abort_unless($submission->assignment_id === $assignment->id, 404);

        $data = $request->validate([
            'submission_type' => ['required', 'in:file,link,video_link'],
            'link_url' => ['nullable', 'url', 'max:500', 'required_if:submission_type,link'],
            'video_url' => ['nullable', 'url', 'max:500', 'required_if:submission_type,video_link'],
            'attachments' => ['required_if:submission_type,file', 'array'],
            'attachments.*' => ['file', 'max:51200'], // 50MB each
            'attachments_remove' => ['array'],
            'attachments_remove.*' => ['integer', 'exists:assignment_submission_attachments,id'],
        ], [
            'attachments.required_if' => 'Please upload at least one file for file submission.',
        ]);

        $submission->update([
            'submission_type' => $data['submission_type'],
            'link_url' => $data['link_url'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            // Keep as turned in when student updates; grading command/logic can adjust to late/graded
            'status' => AssignmentSubmission::STATUS_TURNED_IN,
            'submitted_at' => now(),
        ]);

        // Remove attachments
        if (!empty($data['attachments_remove'])) {
            $attachmentsToRemove = AssignmentSubmissionAttachment::whereIn('id', $data['attachments_remove'])
                ->where('assignment_submission_id', $submission->id)
                ->get();

            foreach ($attachmentsToRemove as $att) {
                if (Storage::disk('public')->exists(str_replace('/storage/', '', $att->url))) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $att->url));
                }
                $att->delete();
            }
        }

        // Add new attachments if submission type is file
        if ($data['submission_type'] === 'file') {
            // Check if there will be any attachments after removal
            $remainingAttachments = $submission->attachments()
                ->whereNotIn('id', $data['attachments_remove'] ?? [])
                ->count();

            // If no existing attachments remain and no new files uploaded, require files
            if ($remainingAttachments === 0 && !$request->hasFile('attachments')) {
                return redirect()->back()
                    ->withErrors(['attachments' => 'Please upload at least one file for file submission.'])
                    ->withInput();
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('assignment-submissions', 'public');
                    AssignmentSubmissionAttachment::create([
                        'assignment_submission_id' => $submission->id,
                        'name' => $file->getClientOriginalName(),
                        'type' => $file->getMimeType(),
                        'url' => Storage::disk('public')->url($path),
                        'size' => $file->getSize(),
                        'version' => 1,
                        'is_current' => true,
                        'folder_id' => $request->input('folder_id'),
                    ]);
                }
            }
        }

        return redirect()->route('student.assignments.show', [$classlist->id, $assignment->id])
            ->with('success', 'Assignment submission updated successfully.');
    }
}
