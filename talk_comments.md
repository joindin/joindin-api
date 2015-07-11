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
        rating: 4
        comment: Suspendisse mattis suscipit ante, nec consectetur magna aliquet non. Aliquam vulputate vulputate lobortis. Suspendisse mattis suscipit ante, nec consectetur magna aliquet non. Proin feugiat mattis dui, ut cursus purus feugiat vel. Vivamus gravida, dolor ut porta bibendum, mauris ligula condimentum est, id facilisis ante massa a justo. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Pellentesque elementum placerat lectus, sit amet dictum urna euismod quis. Sed nisi sem, ultricies et luctus vitae, volutpat id sem. Suspendisse mattis suscipit ante, nec consectetur magna aliquet non. Suspendisse mattis suscipit ante, nec consectetur magna aliquet non. Proin feugiat mattis dui, ut cursus purus feugiat vel. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Pellentesque elementum placerat lectus, sit amet dictum urna euismod quis.
        user_display_name: Raymond Armstrong
        talk_title: Producing public systems together with Apache
        created_date: 2014-01-21T23:34:27+01:00
        uri: http://api.dev.joind.in:8080/v2.1/talk_comments/320
        verbose_uri: http://api.dev.joind.in:8080/v2.1/talk_comments/320?verbose=yes
        talk_uri: http://api.dev.joind.in:8080/v2.1/talks/159
        talk_comments_uri: http://api.dev.joind.in:8080/v2.1/talks/159/comments
        user_uri: http://api.dev.joind.in:8080/v2.1/users/5
meta:
    count: 1
    total: 1
    this_page: http://api.dev.joind.in:8080/v2.1/talks/159/comments?start=0&resultsperpage=20
~~~~

## Talk Comment Verbose Format

~~~~
comments:
    0:
        rating: 4
        comment: Suspendisse mattis suscipit ante, nec consectetur magna aliquet non. Aliquam vulputate vulputate lobortis. Suspendisse mattis suscipit ante, nec consectetur magna aliquet non. Proin feugiat mattis dui, ut cursus purus feugiat vel. Vivamus gravida, dolor ut porta bibendum, mauris ligula condimentum est, id facilisis ante massa a justo. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Pellentesque elementum placerat lectus, sit amet dictum urna euismod quis. Sed nisi sem, ultricies et luctus vitae, volutpat id sem. Suspendisse mattis suscipit ante, nec consectetur magna aliquet non. Suspendisse mattis suscipit ante, nec consectetur magna aliquet non. Proin feugiat mattis dui, ut cursus purus feugiat vel. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Pellentesque elementum placerat lectus, sit amet dictum urna euismod quis.
        user_display_name: Raymond Armstrong
        talk_title: Producing public systems together with Apache
        source: api
        created_date: 2014-01-21T23:34:27+01:00
        gravatar_hash: 846d440ca4afd8553214234c9f883853
        uri: http://api.dev.joind.in:8080/v2.1/talk_comments/320
        verbose_uri: http://api.dev.joind.in:8080/v2.1/talk_comments/320?verbose=yes
        talk_uri: http://api.dev.joind.in:8080/v2.1/talks/159
        talk_comments_uri: http://api.dev.joind.in:8080/v2.1/talks/159/comments
        user_uri: http://api.dev.joind.in:8080/v2.1/users/5
meta:
    count: 1
    total: 1
    this_page: http://api.dev.joind.in:8080/v2.1/talks/159/comments?verbose=yes&start=0&resultsperpage=20
~~~~

Verbose mode adds the ``source`` and ``gravatar_hash`` fields

## Talk Comment Data Fields

The fields available for talk comments:

*  ``rating``:  A rating from 1-5 where 5 is the best and 1 is rubbish.  Can sometimes be zero when the commenter has previously commented or is the speaker at the session.  Anonymous and zero-rated comments are excluded when calculating averages
*  ``comment``: The user's feedback on the session
*  ``source``: Which tool the user made to create this comment (can be empty if we don't have the info)
*  ``user_display_name``: Convenience field showing the display name of the user that made this comment. Empty if anonymous
*  ``created_date``: When the comment was made, in ISO format
*  ``gravatar_hash``: Unique identifier for showing their gravatar image; append this to ``http://www.gravatar.com/avatar/`` to make the image URL

## Talk Comment Hypermedia

*  ``uri``: Where to find this individual record
*  ``verbose_uri``: Where to find a more detailed representation of this record
*  ``talk_uri``: The talk that this comment belongs to
*  ``talk_comments_uri``: Where to find all the comments on the same talk as this comment
*  ``user_uri``: The user record of the commenting user (if there is one, this field isn't present for anonymous comments)

## Commenting on Talks

To comment on a talk, the user must be authenticated and should then send a POST request to the comments collection for the talk in question containing both ``rating`` and ``comment``.

Curl example:

<pre class="embedcurl">curl -v -H "Content-Type: application/json" -H "Authorization: Bearer f9b4f1a9b30bdc0d" /talks/139/comments --data '{"comment": "Great talk, thanks!", "rating": 4}'
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
> Authorization: Bearer f9b4f1a9b30bdc0d
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

## Comments by a specific user

The comments that a given user has created are a [subresource of the user]({{ site.baseurl }}/users.html#sub-resources).
