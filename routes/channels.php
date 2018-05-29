<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/



Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

//this is for private channels. public channels do not need to be defined inside of here.

Broadcast::channel('post.{id}', function ($user, $id) {
    return true;
        //this is set up so that any logged in user will have access and any non-logged in user will not have access.
    
    //if we only wanted to give access to the author of a post, we could do something like return $user->id == \App\Post::find($id)->user_id;
});