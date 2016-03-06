---
layout: page
title: Joind.in API Documentation
---

# Joind.in API Documentation

API documentation, examples and other information about the API for <http://joind.in>, the event feedback site.

## Overview

Joind.in's API follows a RESTful style, is available in JSON and HTML formats (so point your browser at <http://api.joind.in>), and uses OAuth2 for authentication where this is needed; all of the data publicly visible on the site is available via the API without authentication.

We are happy for you to make whatever use of the API you wish (bear in mind we run everything from a single donated server so please implement some caching on your side and be considerate of the traffic levels you send to us). Please mention the source of your data, but do not use "joind.in" in your project name or imply that the joind.in project endorses your project.

## Global Parameters

There are a few parameters supported on every request:

*  ``verbose``: set to ``yes`` to see a more detailed set of data in the responses. This works for individual records and collections.
*  ``start``: for responses which return lists, this will offset the start of the result set which is returned. The default is zero; use in conjuction with resultsperpage
*  ``resultsperpage``: for responses which return lists, set how many results will be returned. The default is currently 20 records; use with start to get large result sets in manageable chunks.  Set to zero for no limits (at your own peril).
*  ``format``: Ideally set a correct ``Accept`` header to specify your preferred format, but if that's not an option then you can set this to ``html`` or ``json`` as appropriate.

## Data Formats

The service currenty supports JSON and HTML, using your ``Accept`` header to establish which format you wanted. In the event that this is not behaving as expected, simply add the ``format`` parameter to specify which format should be returned, if none is specified then JSON is the default behaviour.

If you want to use the data provided by this API from JavaScript, we offer support for JSONP. To use this, request json format data and pass an additional ``callback`` parameter containing the name of the function to call; the results will be the usual JSON but surrounded with the function you named.

Where there are links to other resources, and for pagination, you will find those hypermedia links included as part of the data structure. 

## Meta Data

Results will include a ``meta`` element which contains pagination links, and a count of the number of results that were returned.  If this request was made by an authenticated user, their user details will also be contained in the meta block.

    meta:
    count: 20
    this_page: https://api.joind.in/v2/events/603/talks?resultsperpage=20&start=0
    next_page: https://api.joind.in/v2/events/603/talks?resultsperpage=20&start=20

## Authentication

Read the [authentication howto]({{ site.baseurl }}/howto/authenticate.html) for detailed information on authenticating.

## Howtos

[users attending howto]({{ site.baseurl }}/howto/event_attending.html)

[authentication howto]({{ site.baseurl }}/howto/authenticate.html)

## Detailed documentation per endpoint

*  [Events]({{ site.baseurl }}/events.html)
*  [Talks]({{ site.baseurl }}/talks.html)
*  [Users]({{ site.baseurl }}/users.html)
*  [Talk comments]({{ site.baseurl }}/talk_comments.html)
*  [Event comments]({{ site.baseurl }}/event_comments.html)
*  [Languages]({{ site.baseurl }}/languages.html)
*  [Talk types]({{ site.baseurl }}/talk_types.html)
