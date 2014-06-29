---
layout: page
title: Joind.in API Documentation
---

# Talk Comments

Users can comment on talks.  Currently, users need to be authenticated in order to comment on talks via the API.

## Talk Comment Format

~~~~
comments:
    0:
        rating: 3
        comment: Lorem ipsum dolor sit amet, consectetur adipiscing elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent rutrum orci eget ipsum ornare et consequat neque egestas. Duis eu massa justo, vel mollis velit. Suspendisse mattis suscipit ante, nec consectetur magna aliquet non. Sed nisi sem, ultricies et luctus vitae, volutpat id sem. Vivamus gravida, dolor ut porta bibendum, mauris ligula condimentum est, id facilisis ante massa a justo. Aliquam vulputate vulputate lobortis. Sed nisi sem, ultricies et luctus vitae, volutpat id sem. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed nisi sem, ultricies et luctus vitae, volutpat id sem. Quisque at est libero.
        user_display_name: James Barnes
        talk_title: Producing newtech clouds under Ruby
        created_date: 2013-11-08T02:17:48+01:00
        uri: http://api.joindin.local/v2.1/talk_comments/359
        verbose_uri: http://api.joindin.local/v2.1/talk_comments/359?verbose=yes
        talk_uri: http://api.joindin.local/v2.1/talks/28
        talk_comments_uri: http://api.joindin.local/v2.1/talks/28/comments
        user_uri: http://api.joindin.local/v2.1/users/11
meta:
    count: 1
    this_page: http://api.joindin.local/v2.1/talks/28/comments?start=0&resultsperpage=20
~~~~

## Talk Comment Verbose Format

~~~~
comments:
    0:
        rating: 3
        comment: Lorem ipsum dolor sit amet, consectetur adipiscing elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent rutrum orci eget ipsum ornare et consequat neque egestas. Duis eu massa justo, vel mollis velit. Suspendisse mattis suscipit ante, nec consectetur magna aliquet non. Sed nisi sem, ultricies et luctus vitae, volutpat id sem. Vivamus gravida, dolor ut porta bibendum, mauris ligula condimentum est, id facilisis ante massa a justo. Aliquam vulputate vulputate lobortis. Sed nisi sem, ultricies et luctus vitae, volutpat id sem. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed nisi sem, ultricies et luctus vitae, volutpat id sem. Quisque at est libero.
        user_display_name: James Barnes
        talk_title: Producing newtech clouds under Ruby
        source: web
        created_date: 2013-11-08T02:17:48+01:00
        uri: http://api.joindin.local/v2.1/talk_comments/359
        verbose_uri: http://api.joindin.local/v2.1/talk_comments/359?verbose=yes
        talk_uri: http://api.joindin.local/v2.1/talks/28
        talk_comments_uri: http://api.joindin.local/v2.1/talks/28/comments
        user_uri: http://api.joindin.local/v2.1/users/11
meta:
    count: 1
    this_page: http://api.joindin.local/v2.1/talk_comments/359?verbose=yes&start=0&resultsperpage=20
~~~~

*(the only addition is the ``source`` field)*

## Talk Comment Data Fields

The fields available for talk comments:

*  ``rating``:  A rating from 1-5 where 5 is the best and 1 is rubbish.  Can sometimes be zero when the commenter has previously commented or is the speaker at the session.  Anonymous and zero-rated comments are excluded when calculating averages
*  ``comment``: The user's feedback on the session
*  ``user_display_name``: Convenience field showing the display name of the user that made this comment. Empty if anonymous
*  ``created_date``: When the comment was made, in ISO format


## Talk Comment Hypermedia

*  ``uri``: Where to find this individual record
*  ``verbse_uri``: Where to find a more detailed representation of this record
*  ``talk_uri``: The talk that this comment belongs to
*  ``talk_comments_uri``: Where to find all the comments on the same talk as this comment
*  ``user_uri``: The user record of the commenting user (if there is one, this field isn't present for anonymous comments)

## Commenting on Talks

To comment on a talk, the user must be authenticated and should then send a POST request to the comments collection for the talk in question containing both ``rating`` and ``comment``.

Curl example:

<pre class="embedcurl">curl -v -H "Content-Type: application/json" -H "Authorization: OAuth f9b4f1a9b30bdc0d" /talks/139/comments --data '{"comment": "Great talk, thanks!", "rating": 4}'
</pre>

<!-- You only need to reference this script once per page. -->
<script src="https://www.embedcurl.com/embedcurl.min.js" async></script>

Here's the example request/response (the -v switch ensures that you see the response headers)

~~~~
> POST /v2.1/talks/139/comments HTTP/1.1
> User-Agent: curl/7.32.0
> Host: api.joindin.local
> Accept: */*
> Content-Type: application/json
> Authorization: OAuth f9b4f1a9b30bdc0d
> Content-Length: 47
> 
* upload completely sent off: 47 out of 47 bytes
< HTTP/1.1 201 Created
< Date: Tue, 21 Jan 2014 14:04:21 GMT
* Server Apache/2.4.6 (Ubuntu) is not blacklisted
< Server: Apache/2.4.6 (Ubuntu)
< X-Powered-By: PHP/5.5.3-1ubuntu2.1
< Location: http://api.joindin.local/v2.1/talk_comments/502
< Content-Length: 0
< Content-Type: text/html
< 

~~~~

A 201 status code indicates that the comment was successfully created, and the ``Location`` header gives the location of the new record (or you can choose to just fetch the collection again, it's your choice).  In the case of failure, you will get a 4xx status code and some information about what exactly went wrong.
