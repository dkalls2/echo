@extends('layouts.app')

@section('content')
  <div class="container">
    <h1>{{ $post->title }}</h1>
    {{ $post->updated_at->toFormattedDateString() }}
    @if ($post->published)
      <span class="label label-success" style="margin-left:15px;">Published</span>
    @else
      <span class="label label-default" style="margin-left:15px;">Draft</span>
    @endif
    <hr />
    <p class="lead">
      {{ $post->content }}
    </p>
    <hr />

    <h3>Comments:</h3>
    <div style="margin-bottom:50px;" v-if="user">
      {{-- v-if means that if there is a currently logged in user, we will show the text area below, if not, it will show the v-else --}}
      <textarea class="form-control" rows="3" name="body" placeholder="Leave a comment" v-model = "commentBox"></textarea>
      <button class="btn btn-success" style="margin-top:10px" @click.prevent="postComment">Save Comment</button>
        {{-- @click.prevent means on click, and prevent default (which is to submit and refresh the page, which is what we don't want), run postComment --}}
    </div>
    <div v-else>
        <h4>You must be logged in to submit a comment</h4>
        <a href="/login">Login Now &gt;&gt;</a>
    </div>


    <div class="media" style="margin-top:20px;" v-for="comment in comments">
        {{-- the v-for loops through the entire comments array and every iteration is going to save that current iteration as comment
        Nowe we will have access to this comment variable which represents the current variable of our loop.
        Then every time we have another comment, this media html will get duplicated and we can go through it once again. --}}
      <div class="media-left">
        <a href="#">
          <img class="media-object" src="http://placeimg.com/80/80" alt="...">
          {{-- if the users had an avatar picture, we could replace the placeimg with something like @{{comment.user.avatar}} and it would get their avatar url.--}}
        </a>
      </div>
      <div class="media-body">
        <h4 class="media-heading">@{{comment.user.name}} said...</h4>
        <p>
            @{{comment.body}}
        </p>
        <span style="color: #aaa;">on @{{comment.created_at}}</span>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
  
    const app = new Vue({
        el: '#app',
        data:{
          comments: {},
          commentBox:'',
          post: {!! $post->toJson() !!},
            //this gives you a Json version of all of the post information that was on the page.
          user: {!! Auth::check() ? Auth::user()->toJson() : 'null' !!}
            //this gets the Json version of all the user info on the page.
            //we first check to see if the user is logged in though.  
        },
        mounted() {
          //we want to have getComments run as soon as the page loads.  mounted is a hook that runs at a certain lifecycle of the application.
          //It is when the entire vue instance has mounted.  
          //Mounted means it is all set up and ready to go and the next javascript tick, the vue application will be accpeting inputs and doing everything.
          //mounted happens right before that tick. this means the rest of the vue application is ready to do something and it is one tick away from doing the stuff it is actually supposed to do.
          //this is the perfect time to do anything right at the beginning of an application loading.
          this.getComments();

          this.listen();
            //we want this function also to be ran here so we can listen to new comments to come in right away.

        },
        methods: {
          getComments() {
              //this gets all of the comments from the server, from the API request
              //we need to send an API request to that API endpoint we created in the CommentController file.  
              //We take that info, expect to get a response back with a Json object with all of the comments for this blog post.
              //we send an api request over, request all the comments (it will come back to us in Json) and then we will save that Json in the comments data object in the the Vue object.
              //we will use axios to do this.
              axios.get(`/api/posts/${this.post.id}/comments`)
                //we have the post id from the above post data object in the vue object.
                  .then((response) => {
                    //then when this comes back, we will grab our response and tell it what to do here:
                    this.comments = response.data
                      //response contains all sorts of info like status codes, etc., so we need to do response.data, which is the response from the server.
                      //this means that now the comment object above in the data vue object has all the comments in the json format that this blog post should have.
                  })
                  .catch(function (error){
                      //this is to tell it what to do if there is a problem
                      console.log(error);
                  });
                
          },
          
          postComment() {
            //with this, we want to take and save a comment.  They type something into the text box, click save comment...this is what we want to happen.
            //we will take info form the text area, which is in the commentBox, we will pass that into the store method on the server via the api endpoint we created and we will do that as a post request.
            //similar to getComments() above, but it is a post request.
            axios.post(`/api/posts/${this.post.id}/comment`, {
              //the second parameter is the object that contains everything we pass in with the request.
              //what is needed by the request in order to save that comment? Body, user ID, post id.  
              //We can get user id by currently logged in user, but we do want to pass in the user API token, so the user can be authenticated and we will get their id based onthe authentication.
              //we dont need the post id, becasue that is already passed in from the url.
              api_token: this.user.api_token,
              body: this.commentBox
            })
            .then((response) => {
                //if we don't use => (as opposed to an anonymous function), we won't have access to the "this" parameter in the "then"
                //the "then" tells it what to do when the response comes back.  
                //We send out the request, the request gets saved to the database, then the response that comes back is the comment that was just saved.
                //we want to take that comment that was just saved and add it to the top of the comments array.
                this.comments.unshift(response.data);
                  //unshift is a method that will put something on the top of an array and push everything down.
                  //for the user, the comment appears twice.  Once from this line of code and again when the listen() function below is called and there is the same line of code there too.
                  //to fix this, we could just comment this out and only the comment from the socket server would appear, but there might be a delay 
                  //and it wouldn't appear if there is an error with the socket server.  
                  //It is better not to rely on the web socket for this...web sockets should just enhance the user experience, so this is not a good idea.
                this.commentBox = '';
                  //if we hit submit, we want to clear out the comment text so it is clear again.  
                  //this needs to be done afterwards, because we still need the commentBox info above.
            })
            .catch(function(error){
              console.log(error);
            }); 
          },
          listen() {
            //this is where we put our listeners.  When we run the listen method, it will listen on the channels we created.
            Echo.private('post.'+this.post.id)
              //this actually implements laravel Echo for the first time.  This tells echo which channel to subscribe to.
              //this will subscribe us the current post we are on.\
              //to make this a private channel, we can write Echo.private('post.'+this.post.id).  We would also need to set up our channels.php under routes.
              //to make this a public channel, we can write Echo.channel('post.'+this.post.id).

              .listen('NewComment', (comment) => {
                //this tells echo to listen for certain events, in this case the NewComment event.
                //the second parameter is a function (it could be a => function or an anonymous function).  We are passing in comment, so we have access to that info here.
                
                //we could add an if statement here to say that if comment.user.id !== this.user.id, then do the following code in order to prevent duplicates.
                //BUT with laravel Echo, it is even easier. In the CommentController, instead of event(new NewComment ($comment)), we can change event with broadcast
                //and this will give us access to laravel Echo methods, such as toOthers, which will only sent this event to other people, not us.
                this.comments.unshift(comment);
                  //whenever we hear a NewComment event, we grab the comment from the payload, unshift that comment (place at the beginning of comments array)
                  //and then vue handles the binding to make it appear on the screen as it comes.
              })
                
          }

        }

    });

  </script>
@endsection
