<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Post;
    //this allows us to type hint the url as a post?
use App\Comment;
use Auth;
use App\Events\NewComment;

class CommentController extends Controller
{
    public function index(Post $post)
    {
        return response()->json($post->comments()->with('user')->latest()->get());
            //this returns all the comments for a specific post and also bring in the user information so we can display that with the comments
            //latest sorts with the newest one at the top.
            //because we are using query builder, we use get at the end to actually get the request.
            //lastly, we put this all in json...it gets the request and then returns it as a json in the response.
    }


    public function store(Request $request, Post $post)
    {
        $comment = $post->comments()->create([
            //this is creating a new comment and linking it to post
            'body'=> $request->body,
            'user_id' => Auth::id()
        ]);

        //here we will write over the $comment variable
        $comment = Comment::where('id', $comment->id)->with('user')->first();
            //this says where the id is equal to the id of the one we just saved, we get that info, eager load the user info, and get first object.  And then retur nthat comment back.
            //I guess this is so we can get other info about the user, more than just the id?
        
        
        // Here is where we want to trigger out event that will get passed into the socket server:
        broadcast(new NewComment($comment))->toOthers();
            //$comment gets passed into the NewComment event.  We could use the word broadcast or event.
            //broadcast is an alias for event, but this also gives us access to toOthers
            //toOthers will only broadcast this event to everyone else except for outselves.  We will not see the broadcast.  We are not relying on it.
        
       return $comment->toJson();
    }
}
