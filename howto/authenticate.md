---
layout: page
title: Joind.in API Documentation
---

# How to Authenticate Against the Joind.in API

You only need to authenticate if you're adding or editing data (including comments) or want to access private data. For most operations, particularly just retrieving information, authentication is not required. If you do want to authenticate, you must make all requests over SSL (i.e. to https://api.joind.in)

## tl;dr

It's OAuth2.

## Step 1: Sign up for an API key

The API key is per-application.  You can get this by logging in to your user account on http://joind.in and going to "Account" in the top right corner and then "API Keys" in the box on the right.

This page is in two parts - the first section shows you which applications you have granted access to, and the second part is for your consumer keys.  Create a new key using the form right at the bottom of the screen, tell us a bit about your application (helps us to know which ones to approve).  Copy the key.

## Step 2: Get an Access Token For This User

For each user, you will need to send them over to joind.in to log in as themselves and confirm that they grant your application access to their data.  The URL you want is:

    http://joind.in/user/oauth_allow?api_key=[api-key]&callback=[callback_url]

The ``[api-key]`` should be the key you registered in step 1.  The ``[callback_url]`` must **exactly** match the callback URL you registered when signing up for your API key.  There is an optional additional parameter called ``state``; whatever you set here will be sent back to you as a query parameter when we forward the user back your callback.

When the user arrives on our page they will grant access (logging in first if needed) and when they confirm, we'll forward them back to your callback URL with an ``access_token`` parameter in the query string.

## Step 3:  Use the Access Token To Sign Requests

Capture the access token parameter, and then send it in all requests as part of an Authorization header in the format:

    Authorization: OAuth [access_code]

Where the ``[access_token]`` is the value from the query parameter when the user returned to your site.  All successfully authenticated requests will show a ``user_uri`` field in the ``meta`` block of the response.


