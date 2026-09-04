<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    // saglaba komentārus
    public function store(StoreCommentRequest $request): RedirectResponse
    {
        Comment::create([
            'user_id' => Auth::id(),
            ...$request->validated(),
        ]);

        return back()->with('success', 'Comment created successfully!');
    }

    // dzēš komentārus
    public function destroy(Comment $comment): RedirectResponse
    {
        if ($comment->user_id !== Auth::id()) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'Comment deleted successfully!');
    }
}
