<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index(): View
    {
        $posts = Post::with('user')->latest()->get();

        return view('blog.index', compact('posts'));
    }

    public function create(): View
    {
        return view('blog.create');
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        Post::create([
            'user_id' => Auth::id(),
            ...$request->validated(),
        ]);

        return redirect()->route('blog.index')->with('success', 'Post created successfully!');
    }

    public function show(Post $post): View
    {
        $post->load('comments.user');

        return view('blog.show', compact('post'));
    }

    public function destroy(Post $post): RedirectResponse
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }

        $post->delete();

        return redirect()->route('blog.index')->with('success', 'Post deleted successfully!');
    }
}
