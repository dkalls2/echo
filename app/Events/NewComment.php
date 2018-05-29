<?php

namespace App\Events;

//this event was created by php artisan make:event NewComment.  This will get passed over to pusher

use App\Comment;
use Illuminate\Broadcasting\Channel;
    //this is set up for broadcasting because we set up the broadcast service provider.
    //this is a public broadcasting channel
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
    //this requies someone to be authenticated to have access to the channel
use Illuminate\Broadcasting\PresenceChannel;
    //this is advanced private channel by getting info about who is in the channel.
    //here you can see who is typing, etc. 
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
    //this means it is web sockets compatible.
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
    //this contract says this event should also broadcast to the connection that happened.
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
    //same as ShouldBroadcast, but it does not queue

class NewComment implements ShouldBroadcastNow
    //by adding "implements SHouldBoradcast" means that when this event is triggered, broadcast this event to all of the channels.
    //it broadcasts this over to the socket server and the socket server handles it from there.
    //by default, all broadcasts are going to be queued, so you need to have a queue running in order for a broadcast to be sent out.
    //we could use ShouldBroadcastNow instead of ShouldBroadcast, and it will not use queue, it will send out immediately.
    //when we put this in production, we should use the queue, so we want to use ShouldBroadcast instead of ShouldBroadcastNow and make sure queue is working properly.
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;
        //if you have a public property, the public property will available in the event, but it will also be passed in to the channel in the broadcastOn function.
        //it passes the comment in as part of the payload to the socket server.
        //if we didn't want to pass the comment out to the socket server, we could use protected instead of public here.

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Comment $comment)
    {
        //this needs to be set up so we can accept a new comment coming in and then we want to configure it so we have access to this.
        $this->comment = $comment;
            //this makes the value of comment available throughout the whole event.
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('post.'.$this->comment->post->id);
            //this allows us to say which channel we want to broadcast this on.  Channel means it is a public channel.
            //we want a different channel for every single blog post, not one channel for all of the posts.  We need to create a dynamic channel.
            //we needed to pass in the comment info in the _construct public function in order for this event to have access to this variable.
            // To make this a private chanel, we just write return new PrivateChannel('post.'.$this->comment->post->id);
            // To make this a public chanel, we just write return new Channel('post.'.$this->comment->post->id);
    }


    //here are some other functions we could use

    // public function broadcastAs()
    //     //this allows you to name the event.  By default it is called NewComment (as shown above).  With this function, we can change it to anything we want.
    // {
    //     return 'comment-available';
    // }

    public function broadcastWith()
        //this allows oyu to customize the payload that is pushed to the socket server.  This overrides what gets passed in.
        //it is almost always a good idea to use broadcastWith.  Otherwise, we may pass in sensitive information about a user tha tmight go public (like their api token, for example)
    {
        return [
            'body' => $this->comment->body,
            'created_at' => $this->comment->created_at->toFormattedDateString(),
            'user' => [
                'name' => $this->comment->user->name,
                'avatar' => 'http://lorempixel/50/50',
                    //we do not have an avatar, so we are hardcoding this.
            ]
        ];
    }
}
