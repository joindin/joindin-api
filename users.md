---
layout: page
title: Joind.in API Documentation
---

## Users

Users appear across the API, sometimes called other things.  You can find collections of users in an event where they are hosts or attendees, in talks where they are speakers, linked from comments where they are the author, and elsewhere - if you are authenticated then the URI for your own user record will be included in the ``meta`` block of each response.

The user records offer some information about a user, and also allow access to linked items such as talks or events.

## User Format

The user record looks something like this:

~~~~
users:
    0:
        username: sperez
        full_name: Sharon Perez
        twitter_username:
        uri: http://api.dev.joind.in:8080/v2.1/users/2
        verbose_uri: http://api.dev.joind.in:8080/v2.1/users/2?verbose=yes
        website_uri: http://joind.in/user/view/2
        talks_uri: http://api.dev.joind.in:8080/v2.1/users/2/talks/
        attended_events_uri: http://api.dev.joind.in:8080/v2.1/users/2/attended/
meta:
    count: 1
    total: 1
    this_page: http://api.dev.joind.in:8080/v2.1/users/2?start=0&resultsperpage=20
~~~~

## Verbose User Format

The verbose user format simply include the ``gravatar_hash`` field.

## User Fields

*  ``username``: The user's username that they use to log in with; this will be unique
*  ``full_name``: The user's name - this is used as their display name in most cases
*  ``twitter_username``: If the user supplied their twitter username, it is here
*  ``gravatar_hash``: Unique identifier for showing their gravatar image; append this to ``http://www.gravatar.com/avatar/`` to make the image URL
*  ``website_uri``: Where to find this user's joind.in page on the web

## User Hypermedia

*  ``uri``: Where to find this record
*  ``verbose_uri``:  Where to find the detailed version of this record (user records are the same in both formats)
*  ``talks_uri``: Talks given by this user. *See also* [talks]({{ site.baseurl}})/talks.html
*  ``attended_events_uri``: The events that this user was/will be at

## Filtering the Users Collection

You can filter by ``username``, e.g. ``{{ site.apiurl }}/v2.1/users?username=lornajane``

## Sub resources

The user resource has the `talk_comments` subresource which contains all the
comments created by this user ordered by reverse date created.

The collection looks something like this:

~~~~
comments:
    0:
        rating: 4
        comment: Sed nisi sem, ultricies et luctus vitae, volutpat id sem. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Pellentesque elementum placerat lectus, sit amet dictum urna euismod quis. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Pellentesque elementum placerat lectus, sit amet dictum urna euismod quis. Duis eu massa justo, vel mollis velit. Vivamus gravida, dolor ut porta bibendum, mauris ligula condimentum est, id facilisis ante massa a justo. Aliquam vulputate vulputate lobortis. Etiam ligula elit, condimentum lacinia fermentum nec, elementum id urna. Vivamus gravida, dolor ut porta bibendum, mauris ligula condimentum est, id facilisis ante massa a justo. Proin feugiat mattis dui, ut cursus purus feugiat vel. Nulla faucibus mollis ipsum sit amet vehicula. Proin feugiat mattis dui, ut cursus purus feugiat vel. Etiam ligula elit, condimentum lacinia fermentum nec, elementum id urna. Duis eu massa justo, vel mollis velit. Aliquam vulputate vulputate lobortis.
        user_display_name: Sandra Warren
        talk_title: Controlling flexible systems with Perl
        created_date: 2014-09-11T19:59:06+02:00
        uri: http://api.dev.joind.in:8080/v2.1/talk_comments/338
        verbose_uri: http://api.dev.joind.in:8080/v2.1/talk_comments/338?verbose=yes
        talk_uri: http://api.dev.joind.in:8080/v2.1/talks/163
        talk_comments_uri: http://api.dev.joind.in:8080/v2.1/talks/163/comments
        user_uri: http://api.dev.joind.in:8080/v2.1/users/2

meta:
    count: 1
    total: 1
    this_page: http://api.dev.joind.in:8080/v2.1/users/2/talk_comments?start=0&resultsperpage=20
~~~~


