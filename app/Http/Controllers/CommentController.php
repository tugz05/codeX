<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Activity;
use App\Models\Assignment;
use App\Models\Material;
use App\Models\ActivitySubmission;
use App\Models\AssignmentSubmission;
use App\Models\Classlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'commentable_type' => ['required', 'string'],
            'commentable_id' => ['required', 'integer'],
            'content' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            'classlist_id' => ['nullable', 'string', 'exists:classlists,id'],
            'visibility' => ['nullable', 'string', 'in:public,private'],
        ]);

        // Normalize commentable_type (handle both single and double backslashes)
        $normalizedType = str_replace('\\\\', '\\', $data['commentable_type']);
        
        // Validate commentable_type after normalization
        $allowedTypes = [
            'App\\Models\\Activity',
            'App\\Models\\Assignment',
            'App\\Models\\Material',
            'App\\Models\\ActivitySubmission',
            'App\\Models\\AssignmentSubmission',
        ];
        
        if (!in_array($normalizedType, $allowedTypes)) {
            return redirect()->back()->withErrors(['commentable_type' => 'Invalid commentable type.']);
        }
        
        $data['commentable_type'] = $normalizedType;

        // Verify the commentable exists and user has access
        $commentable = $this->getCommentable($data['commentable_type'], $data['commentable_id']);
        if (!$commentable) {
            return redirect()->back()->withErrors(['commentable' => 'The item you are trying to comment on does not exist.']);
        }

        // Verify user has access to the classlist
        if ($data['classlist_id']) {
            $enrolled = DB::table('classlist_user')
                ->where('classlist_id', $data['classlist_id'])
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->exists();
            
            // Also check if user is the instructor
            $isInstructor = Classlist::where('id', $data['classlist_id'])
                ->where('user_id', Auth::id())
                ->exists();

            abort_unless($enrolled || $isInstructor, 403);
        }

        // Set default visibility: public for instructors, or use provided value
        $visibility = $data['visibility'] ?? 'public';
        // If user is instructor, always make it public (instructors don't need privacy)
        if (Auth::user()->account_type === 'instructor') {
            $visibility = 'public';
        }

        $comment = Comment::create([
            'commentable_type' => $data['commentable_type'],
            'commentable_id' => $data['commentable_id'],
            'user_id' => Auth::id(),
            'content' => $data['content'],
            'parent_id' => $data['parent_id'] ?? null,
            'classlist_id' => $data['classlist_id'] ?? $commentable->classlist_id ?? null,
            'visibility' => $visibility,
        ]);

        $comment->load('user', 'replies.user');

        return redirect()->back()->with('success', 'Comment added successfully.');
    }

    public function update(Request $request, Comment $comment)
    {
        abort_unless($comment->user_id === Auth::id(), 403);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $comment->update([
            'content' => $data['content'],
        ]);

        return redirect()->back()->with('success', 'Comment updated successfully.');
    }

    public function destroy(Comment $comment)
    {
        abort_unless($comment->user_id === Auth::id(), 403);

        $comment->delete();

        return redirect()->back()->with('success', 'Comment deleted successfully.');
    }

    private function getCommentable(string $type, int $id)
    {
        return match ($type) {
            'App\Models\Activity' => Activity::find($id),
            'App\Models\Assignment' => Assignment::find($id),
            'App\Models\Material' => Material::find($id),
            'App\Models\ActivitySubmission' => ActivitySubmission::find($id),
            'App\Models\AssignmentSubmission' => AssignmentSubmission::find($id),
            default => null,
        };
    }
}
