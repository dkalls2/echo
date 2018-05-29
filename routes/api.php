<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('posts/{post}/comments', 'CommentController@index');
    //anyone should be able to get, or view, the comments in a post.

Route::middleware('auth:api')->group(function () {
    Route::post('posts/{post}/comment', 'CommentController@store');
    //only those who are logged in can post a comment.
});
