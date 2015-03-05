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
        username: lornajane
        full_name: Lorna Mitchell
        twitter_username: lornajane
        gravatar_hash: f6bb323eb6b2ad7f5ca2f8f3fc15f887
        uri: http://api.joind.in/v2.1/users/110
        verbose_uri: http://api.joind.in/v2.1/users/110?verbose=yes
        website_uri: http://joind.in/user/view/110
        talks_uri: http://api.joind.in/v2.1/users/110/talks/
        attended_events_uri: http://api.joind.in/v2.1/users/110/attended/
        hosted_events_uri: http://api.joind.in/v2.1/users/110/hosted/
        talk_comments_uri: http://api.joind.in/v2.1/users/110/talk_comments/
meta:
    count: 1
    total: 1
    this_page: http://api.joind.in/v2.1/users/110?verbose=yes&start=0&resultsperpage=20
~~~~

## Verbose User Format

The verbose user format simply includes the ``gravatar_hash`` field.

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
*  ``hosted_events_uri``: The events that this user is/was event host for
*  ``talk_comments_uri``: All the comments made by this user on talks

## Filtering the Users Collection

You can filter by ``username``, e.g. ``{{ site.apiurl }}/v2.1/users?username=lornajane``

## Creating a User

To create a user, POST to the `/users` collection.  The following fields are required:

 * ``username``
 * ``full_name``
 * ``email``
 * ``password``

Optionally, you can also send the ``twitter_username`` field.

Your request should look something like this:

~~~
{
  "username": "testuser",
  "password": "qwerty",
  "email": "test@example.com",
  "full_name": "Test User",
  "twitter_username": "mytwitterhandle"
}
~~~

Newly-created users are not verified, they will receive an email with a link (pointing to <http://m.joind.in>) that they need to click on before their account becomes active.

## Sub resources

### /talk_comments

The user resource has the `talk_comments` subresource which contains all the
comments created by this user ordered by reverse date created.

See also: </talk_comments.html>

### /talks

The talks given by this user, with the newest first.  Includes talks not given yet.

See also: </talks.html>

### /attended

All the events a user is marked as attending, in descending date order (including future events)

See also: </events.html>

### /hosted

The events that a user is the host for, in descending date order and including future events.

See also: </events.html>

