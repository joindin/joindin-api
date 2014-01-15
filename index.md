---
layout: page
title: Joind.in API Documentation
---

# Joind.in API Documentation

API documentation, examples and other information about the API for http://joind.in, the event feedback site.


## Overview

Joind.in's API follows a RESTful style, is available in JSON and HTML formats (so point your browser at http://api.joind.in), and uses OAuth2 for authentication where this is needed; all of the data publicly visible on the site is available via the API without authentication.

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

You only need to authenticate if you're adding or editing data (including comments) or want to access private data. For most operations, particularly just retrieving information, authentication is not required. If you do want to authenticate, you must make all requests over SSL (i.e. to https://api.joind.in)

This API uses OAuth2. To authenticate you will need the following:

*  Every app must first register for an API key and give the callback that they will use to send users to. To register an API key, sign in to joind.in and visit: https://joind.in/user/apikey. These are associated with your user account, you can have as many as you like and you can delete them at any time.
*  When you want a user to grant you access to their data, send them to: https://joind.in/user/oauth_allow with the following query variables on the URL:
    -  ``api_key`` The key you registered for in step 1 (the secret isn't currently used)
    -  ``callback`` The callback URL to send the user to afterwards. This can be a device URL and it must match the URL you registered in step 1 (exactly match)
    -  ``state`` *(optional)* Whatever you pass in here will be passed back with the user when we redirect them back to you. Use it however you like
*  When the user is sent to the redirect URL, it will contain one additional parameter: ``access_token``. Capture this and store it - this is a per-user token. If authorisation is denied, the access_token parameter will not be present on the callback.
*  To make requests with access to that user's data, add the access token into an authorisation header. The format should be: 

    Authorization: OAuth [access_code]

[events]({{ site.baseurl }}/events.html)
