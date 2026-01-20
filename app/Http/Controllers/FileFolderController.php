<?php

namespace App\Http\Controllers;

use App\Models\FileFolder;
use App\Models\AssignmentSubmission;
use App\Models\AssignmentSubmissionAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FileFolderController extends Controller
{
    /**
     * Get folders for a submission
     */
    public function index(Request $request)
    {
        $submissionId = $request->input('submission_id');
        $submissionType = $request->input('submission_type', 'AssignmentSubmission');
        
        $folders = FileFolder::where('user_id', Auth::id())
            ->where('folderable_type', 'App\\Models\\' . $submissionType)
            ->where('folderable_id', $submissionId)
            ->with('children')
            ->orderBy('order')
            ->get();

        return response()->json($folders);
    }

    /**
     * Store a new folder
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:file_folders,id'],
            'folderable_type' => ['required', 'string'],
            'folderable_id' => ['required', 'integer'],
        ]);

        $folder = FileFolder::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'folderable_type' => 'App\\Models\\' . $validated['folderable_type'],
            'folderable_id' => $validated['folderable_id'],
            'user_id' => Auth::id(),
            'order' => 0,
        ]);

        return response()->json($folder);
    }

    /**
     * Update a folder
     */
    public function update(Request $request, FileFolder $folder)
    {
        abort_unless($folder->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $folder->update($validated);

        return response()->json($folder);
    }

    /**
     * Delete a folder
     */
    public function destroy(FileFolder $folder)
    {
        abort_unless($folder->user_id === Auth::id(), 403);

        DB::transaction(function () use ($folder) {
            // Move files to parent folder or root
            AssignmentSubmissionAttachment::where('folder_id', $folder->id)
                ->update(['folder_id' => $folder->parent_id]);

            // Move child folders to parent
            FileFolder::where('parent_id', $folder->id)
                ->update(['parent_id' => $folder->parent_id]);

            $folder->delete();
        });

        return response()->json(['success' => true]);
    }

    /**
     * Move file to folder
     */
    public function moveFile(Request $request)
    {
        $validated = $request->validate([
            'file_id' => ['required', 'integer'],
            'folder_id' => ['nullable', 'integer', 'exists:file_folders,id'],
            'file_type' => ['required', 'in:assignment_submission'],
        ]);

        $file = AssignmentSubmissionAttachment::findOrFail($validated['file_id']);
        
        // Verify ownership through submission
        $submission = $file->submission;
        abort_unless($submission->user_id === Auth::id(), 403);

        // Verify folder ownership if provided
        if ($validated['folder_id']) {
            $folder = FileFolder::findOrFail($validated['folder_id']);
            abort_unless($folder->user_id === Auth::id(), 403);
        }

        $file->update(['folder_id' => $validated['folder_id']]);

        return response()->json(['success' => true]);
    }
}
