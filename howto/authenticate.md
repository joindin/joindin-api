---
layout: page
title: Joind.in API Documentation
---

# How to Authenticate Against the Joind.in API

You only need to authenticate if you're adding or editing data (including comments) or want to access private data. For most operations, particularly just retrieving information, authentication is not required. If you do want to authenticate, you must make all requests over SSL (i.e. to https://api.joind.in)

## Step 1: Create a Client for users to authenticate against.

First you need a client with a key and secret to use.  You can get this by logging in to your user account on http://joind.in , clicking on your name in the top bar, and choosing "Clients" from the top section of your profile page.

Choose "Add new Client" and fill in the details accordingly. The callback URL isn't used (this API was OAuth2 at one time) so enter whatever you like here BUT KEEP A RECORD OF IT.

## Step 2: Get an Access Token For This User

For each user that uses your client application, you need to log them into joind.in as themselves and get them a user-specific access token. To do this, send an API call like this:

    curl --data '{"grant_type":"password","username":"<username>","password":"<password>","client_id":"<consumer-key>","client_secret":"<consumer-secret"}' -H "Content-Type: application/json" -H "Accept: application/json" http://api.joind.in/v2.1/token

The ``[consumer-key]`` and ``[consumer-secret]`` are the credentials you registered in step 1.  The ``[callback_url]`` must **exactly** match the callback URL you registered at that time.

The access token is included in the response if the user can be successfully authenticated.

## Step 3:  Use the Access Token To Sign Requests

Capture the access token parameter that is returned and then send it in all requests as part of an Authorization header in the format:

    Authorization: Bearer [access_token]

All successfully authenticated requests will show a ``user_uri`` field in the ``meta`` block of the response.


