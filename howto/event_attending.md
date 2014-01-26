---
layout: page
title: Joind.in API Documentation
---

## Users, Events and Attending

A quick roundup of how to find/various elements of the relationship between users and the events they attend.

## Which users are attending this event?

Each event has an ``attendees_uri`` (look out for the similarly-named attending_uri) which points to a collection of users attending this event.

## Is this user attending this event?

If the user is authenticated when making the request (see the [overview]({{ site.baseurl}}) for information about authentication, and check that the ``user_uri`` appears in the ``meta`` block of the response to confirm a user is logged in) and they **are attending** this event, the ``attending`` field will be *true*.

## Which events is a user attending?

Each user has an ``attended_events_uri`` link which is a collection of  all the events they marked themselves as attending (past and future events).

## Mark this user as attending an event.

As an authenticated user, make a POST request (it doesn't need a body) to the ``attending_uri`` listed in the event representation.

## Mark this user as not attending an event.

As an authenticated user, make a DELETE request (it doesn't need a body) to the ``attending_uri`` listed in the event representation.

