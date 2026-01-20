<?php

namespace App\Http\Controllers;

use App\Models\AssignmentSubmissionAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileVersionController extends Controller
{
    /**
     * Get version history for a file
     */
    public function index(Request $request, AssignmentSubmissionAttachment $attachment)
    {
        abort_unless($attachment->submission->user_id === Auth::id(), 403);

        $versions = AssignmentSubmissionAttachment::where(function ($query) use ($attachment) {
            $query->where('id', $attachment->id)
                ->orWhere('parent_attachment_id', $attachment->id)
                ->orWhere(function ($q) use ($attachment) {
                    $q->where('parent_attachment_id', $attachment->parent_attachment_id)
                        ->whereNotNull('parent_attachment_id');
                });
        })
        ->orderBy('version', 'desc')
        ->get();

        return response()->json($versions);
    }

    /**
     * Create a new version of a file
     */
    public function store(Request $request, AssignmentSubmissionAttachment $attachment)
    {
        abort_unless($attachment->submission->user_id === Auth::id(), 403);
        abort_unless($attachment->submission->status !== 'graded', 403); // Can't update after grading

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'version_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Mark current version as not current
        $attachment->update(['is_current' => false]);

        // Get next version number
        $maxVersion = AssignmentSubmissionAttachment::where(function ($query) use ($attachment) {
            $query->where('parent_attachment_id', $attachment->parent_attachment_id ?? $attachment->id)
                ->orWhere('id', $attachment->parent_attachment_id ?? $attachment->id);
        })->max('version') ?? $attachment->version;

        $nextVersion = $maxVersion + 1;

        // Store new file
        $file = $request->file('file');
        $path = $file->store('assignment-submissions', 'public');

        $newVersion = AssignmentSubmissionAttachment::create([
            'assignment_submission_id' => $attachment->assignment_submission_id,
            'name' => $file->getClientOriginalName(),
            'type' => $file->getMimeType(),
            'url' => Storage::disk('public')->url($path),
            'size' => $file->getSize(),
            'version' => $nextVersion,
            'parent_attachment_id' => $attachment->parent_attachment_id ?? $attachment->id,
            'version_notes' => $validated['version_notes'] ?? null,
            'is_current' => true,
            'folder_id' => $attachment->folder_id,
        ]);

        return redirect()->back()->with('success', 'New version uploaded successfully.');
    }

    /**
     * Restore a previous version
     */
    public function restore(AssignmentSubmissionAttachment $attachment)
    {
        abort_unless($attachment->submission->user_id === Auth::id(), 403);
        abort_unless($attachment->submission->status !== 'graded', 403);

        // Mark all versions as not current
        AssignmentSubmissionAttachment::where('assignment_submission_id', $attachment->assignment_submission_id)
            ->where(function ($query) use ($attachment) {
                $query->where('parent_attachment_id', $attachment->parent_attachment_id ?? $attachment->id)
                    ->orWhere('id', $attachment->parent_attachment_id ?? $attachment->id);
            })
            ->update(['is_current' => false]);

        // Mark selected version as current
        $attachment->update(['is_current' => true]);

        return response()->json(['success' => true]);
    }
}
