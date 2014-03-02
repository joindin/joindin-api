---
layout: page
title: Joind.in API Documentation
---

# Talks

Talks can represent more than just a talk, you may also find keynotes, tutorials, and social events masquerading under the heading of "talks".  There is no general talks collection, instead you can find talks by event or by speaker.

## Talk Format

When requesting a talk, you will receive a response like this (using sample data):

```
talks:
    0:
        talk_title: Refactoring fantastic systems instead of NoSQL
        url_friendly_talk_title: refactoring-fantastic-systems-instead-of-nosql
        talk_description: Duis eu massa justo, vel mollis velit.
        type: Talk
        start_date: 2013-11-24T14:37:55+01:00
        duration: 60
        stub: ed55e
        average_rating: 3
        comments_enabled: 1
        comment_count: 2
        starred: false
        starred_count: 3
        speakers:
            0:
                speaker_name: Jeffrey Montgomery
        tracks:
            0:
                track_name: Track 1
                track_uri: {{ site.apiurl }}/v2.1/tracks/3
        uri: {{ site.apiurl }}/v2.1/talks/76
        verbose_uri: {{ site.apiurl }}/v2.1/talks/76?verbose=yes
        website_uri: http://joind.in/talk/view/76
        comments_uri: {{ site.apiurl }}/v2.1/talks/76/comments
        verbose_comments_uri: {{ site.apiurl }}/v2.1/talks/76/comments?verbose=yes
        event_uri: {{ site.apiurl }}/v2.1/events/3
        starred_uri: {{ site.apiurl }}/v2.1/talks/76/starred
meta:
    count: 1
    this_page: {{ site.apiurl }}/v2.1/talks/76?start=0&resultsperpage=20
```

## Verbose Talk Format

If you use the verbose URI to fetch a talk record, the following fields will be included:

```
talks:
    0:
        talk_title: Refactoring fantastic systems instead of NoSQL
        url_friendly_talk_title: refactoring-fantastic-systems-instead-of-nosql
        talk_description: Duis eu massa justo, vel mollis velit.
        type: Talk
        slides_link: http://slideshare.net/slidefromuser
        language: Nederlands
        start_date: 2013-11-24T14:37:55+01:00
        duration: 60
        stub: ed55e
        average_rating: 3
        comments_enabled: 1
        comment_count: 2
        starred: false
        starred_count: 3
        speakers:
            0:
                speaker_name: Jeffrey Montgomery
        tracks:
            0:
                track_name: Track 1
                track_uri: {{ site.apiurl }}/v2.1/tracks/3
        uri: http://api.joindin.local/v2.1/talks/76
        verbose_uri: http://api.joindin.local/v2.1/talks/76?verbose=yes
        website_uri: http://joind.in/talk/view/76
        comments_uri: http://api.joindin.local/v2.1/talks/76/comments
        verbose_comments_uri: http://api.joindin.local/v2.1/talks/76/comments?verbose=yes
        event_uri: http://api.joindin.local/v2.1/events/3
        starred_uri: {{ site.apiurl }}/v2.1/talks/76/starred
meta:
    count: 1
    this_page: http://api.joindin.local/v2.1/talks/76?verbose=yes&start=0&resultsperpage=20
```

## Talk Data Fields

The following fields can be found in a talk record, they are described below:

*  ``talk_title``: The title of the talk
*  ``url_friendly_talk_title``: A version of the talk title that is safe to use in URLs.  It is inflected, and unique per-event  but not system-wide
*  ``talk_description``: Detail about the talk or session (usually the talk abstract)
*  ``type``: Can be one of Talk, Keynote, Workshop, Social, or Event related.  Describes what kind of session it is
*  ``slides_link``: Where to find the slides for this talk if the speaker chose to share them
*  ``language``: The language that this session will be given in.
*  ``start_date``: The start date and time of the session in ISO format
*  ``duration``: The length of the session (usually including breaks, so start_date plus duration gives end time)
*  ``stub``: a system-wide unique identifier.  Used to create the quicklink URLs for a talk
*  ``average_rating``: A calculcated field showing the average of ratings on this talk (excludes anonymous ratings)
*  ``comments_enabled``: Whether comments are currently being accepted on this talk
*  ``comment_count``: Calculated field showing how many comments were made on this talk
*  ``starred``: If the user is authenticated, boolean value indicating whether the user has starred this talk or not.  For unauthenticated users, this field will always be false.
*  ``starred_count``: Calculated field showing how many times this talk has been starred.
*  ``speakers``: An array of the speakers giving the session.  If the speaker has claimed the talk, this includes a speaker_uri which points to their user record.  *See also* [users]({{ site.baseurl}}/users.html)
*  ``tracks``: An array of the tracks that this talk is in, if any.  Shows track labels and a URI for each track.
*  ``website_uri``: The page for this talk on the main joind.in website

## Talk Hypermedia

The following links are available in the talk data format:

*  ``uri``: The identifier and location for this talk
*  ``verbose_uri``: The location for the verbose version of this talk
*  ``comments_uri``: A collection of all talk comments on this talk, sorted by date order with the oldest first.   *See also* [talk comments]({{ site.baseurl }}/talks_comments.html) 
*  ``verbose_comments_uri``: A collection of talk comments on this talk, returned in verbose format and chronological order, oldest first.   *See also* [talk comments]({{ site.baseurl }}/talk_comments.html) 
*  ``event_uri``:  Where to find the event that this talk was given at.
*  ``starred_uri``: POST/DELETE to this URI as an authenticated user to update this talk's starred status.  You could use this field to indicate favourited talks, planned attendance or similar (current starred status is in the ``starred`` field).

## Creating New Talks

To create a talk, you actually need to make a POST request to the *talks collection of the event* that the session belongs to, and send the data in JSON format in the body of the request.  The user making the request must be authenticated and have permissions to create a talk here - either by being a site administrator, or by being a host on the event itself.

The following fields are understood:

*  ``talk_title``: The title of the talk
*  ``talk_description``: Detail about the talk or session (usually the talk abstract)
*  ``language``: One of the following:
     - English - US        
     - English - UK *(default)*
     - Deutsch             
     - Nederlands          
     - Francais            
     - Espanol             
     - Polish              
     - Finnish             
     - Brazilian Portuguese
*  ``start_date``: The start date and time of the session in ISO format
*  ``speakers``: An array of names of speakers (still an array if there is only one speaker)

Other fields are not yet supported.  Editing of events via the API is not yet supported.


