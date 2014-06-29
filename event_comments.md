---
layout: page
title: Joind.in API Documentation
---

## Event Comments

Users can comment on events.  Currently the user must be signed in before they can comment (anonymous options are likely to appear in the future).

## Event Comments Format

Event comments are available from the location in the ``comments_uri`` of an event.  The format looks like this:

~~~~
comments:
    0:
        comment: Proin feugiat mattis dui, ut cursus purus feugiat vel. Etiam ligula elit, condimentum lacinia fermentum nec, elementum id urna. Etiam ligula elit, condimentum lacinia fermentum nec, elementum id urna. Proin feugiat mattis dui, ut cursus purus feugiat vel. Vivamus gravida, dolor ut porta bibendum, mauris ligula condimentum est, id facilisis ante massa a justo. Etiam ligula elit, condimentum lacinia fermentum nec, elementum id urna. Vivamus gravida, dolor ut porta bibendum, mauris ligula condimentum est, id facilisis ante massa a justo. Vivamus gravida, dolor ut porta bibendum, mauris ligula condimentum est, id facilisis ante massa a justo.
        created_date: 2013-10-03T16:30:02+02:00
        user_display_name: Maria Hansen
        user_uri: http://api.joindin.local/v2.1/users/10
        comment_uri: http://api.joindin.local/v2.1/event_comments/22
        verbose_comment_uri: http://api.joindin.local/v2.1/event_comments/22?verbose=yes
        event_uri: http://api.joindin.local/v2.1/events/31
        event_comments_uri: http://api.joindin.local/v2.1/events/31/comments
~~~~

## Event Comments Verbose Format

Exactly the same as event comments format.

## Event Comments Data Fields

The fields in an event comment are as follows:

*  ``comment``: The comment made by the user
*  ``created_date``:  The date that this comment was made, in ISO format
*  ``user_display_name``: This is a convenience field (and it's optional, the original website supports anonymous event comments) so you can show a user name with the comment, it relies on the user_uri

## Event Comments Hypermedia

*  ``user_uri``:  The identifier of the user that made the comment (optional, the old site didn't require sign-in)
*  ``comment_uri``:  The identifier of this record
*  ``verbose_comment_uri``: The verbose representation of this record
*  ``event_uri``: The event this comment relates to
*  ``event_comments_uri``:  Where to find all the comments for this event


## Adding Comments To Events

You can add comments to events via the API; you must be authenticated to do so.

To create a new comment, POST the comment body in an array element called ``comment`` to the event comments collection that you want to add it to.  The API will pick up your identity and add the timestamp.  E.g (using curl against my test system):

<pre class="embedcurl">curl -v -H "Content-Type: application/json" -H "Authorization: OAuth f9b4f1a9b30bdc0d" -X POST http://api.joindin.local/v2.1/events/31/comments --data '{"comment": "Wonderful event, thanks!"}'
</pre>

<!-- You only need to reference this script once per page. -->
<script src="https://www.embedcurl.com/embedcurl.min.js" async></script>

The ``-v`` switch is there so that you see the whole response, which looks something like this:

~~~~
> POST /v2.1/events/31/comments HTTP/1.1
> User-Agent: curl/7.32.0
> Host: api.joindin.local
> Accept: */*
> Content-Type: application/json
> Authorization: OAuth f9b4f1a9b30bdc0d
> Content-Length: 34
> 
* upload completely sent off: 34 out of 34 bytes
< HTTP/1.1 201 Created
< Date: Sun, 19 Jan 2014 21:51:37 GMT
* Server Apache/2.4.6 (Ubuntu) is not blacklisted
< Server: Apache/2.4.6 (Ubuntu)
< X-Powered-By: PHP/5.5.3-1ubuntu2.1
< Location: http://api.joindin.local/v2.1/event_comments/204
< Content-Length: 0
< Content-Type: text/html
~~~~

The ``Location`` header will point to the newly-created comment, and the status code of 201 indicates that all went well.  If anything does go wrong, you will get a 4xx status code response with a message indicating what the problem is.
