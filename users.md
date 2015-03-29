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
        email: lornajane@example.org
        admin: 0
        uri: http://api.joind.in/v2.1/users/110
        verbose_uri: http://api.joind.in/v2.1/users/110?verbose=yes
        website_uri: http://joind.in/user/view/110
        talks_uri: http://api.joind.in/v2.1/users/110/talks/
        attended_events_uri: http://api.joind.in/v2.1/users/110/attended/
        hosted_events_uri: http://api.joind.in/v2.1/users/110/hosted/
        talk_comments_uri: http://api.joind.in/v2.1/users/110/talk_comments/
        can_edit: false
meta:
    count: 1
    total: 1
    this_page: http://api.joind.in/v2.1/users/110?verbose=yes&start=0&resultsperpage=20
~~~~

## Verbose User Format

The verbose user format includes the ``gravatar_hash``, ``email``, admin`` and ``can_edit`` fields.

## User Fields

*  ``username``: The user's username that they use to log in with; this will be unique
*  ``full_name``: The user's name - this is used as their display name in most cases
*  ``twitter_username``: If the user supplied their twitter username, it is here
*  ``gravatar_hash``: Unique identifier for showing their gravatar image; append this to ``http://www.gravatar.com/avatar/`` to make the image URL
*  ``email``: The user's email address. Only visible to the current user or a site admin.
*  ``admin``: Whether this user is a site admin. Only visible if the current user is a site admin.
*  ``website_uri``: Where to find this user's joind.in page on the web
*  ``can_edit``: Whether the current user can edit this user or not.

## User Hypermedia

*  ``uri``: Where to find this record
*  ``verbose_uri``:  Where to find the detailed version of this record (user records are the same in both formats)
*  ``talks_uri``: Talks given by this user. *See also* [talks]({{ site.baseurl }}/talks.html)
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

### Verifying Users

Users will receive an email with a link in it, of a format like: <http://m.joind.in/user/verification?token=81efac8e947dcd98>.  This is used to contact the website which then sends an API POST request containing the token to the ``/users/verifications`` endpoint.  The request should look like this:

~~~
{"token":"81efac8e947dcd98"}
~~~

The API returns an empty response with a status code of 204 (No Content), unless an error occurs in which case a standard error response will be returned.

#### Email New Verification Token

If the user needs to have their verification email sent to them again, then make a POST request to ``/emails/verifications`` with the user's email address in the "email" field.  If an unverified user with matching email address is found, the system will send a new token to the user by email and return a status of 202 (Accepted).

#### Email Username Reminder

For a user who doesn't know their login because they don't know their username, we offer a username reminder feature.  POST to ``/emails/reminders/username`` with the user's email address in the "email" field.  If a user exists with that email address, we will email to there with a reminder of the username and a link to log in on web2.

## Sub resources

### /talk_comments

The user resource has the `talk_comments` subresource which contains all the
comments created by this user ordered by reverse date created.

See also: [Talk Comments]({{ site.baseurl }}/talk_comments.html)

### /talks

The talks given by this user, with the newest first.  Includes talks not given yet.

See also: [Talks]({{ site.baseurl }}/talks.html)

### /attended

All the events a user is marked as attending, in descending date order (including future events)

See also: [Events]({{ site.baseurl }}/events.html)

### /hosted

The events that a user is the host for, in descending date order and including future events.

See also: [Events]({{ site.baseurl }}/events.html)

