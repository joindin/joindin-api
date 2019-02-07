---
layout: page
title: Talk Types
---

It is possible to fetch a read-only list of talk types, or a single talk type

## Format

~~~~
talk_types:
    0:
        title: Talk
        description: Talk
        uri: http://api.dev.joind.in/v2.1/talk_types/1
        verbose_uri: http://api.dev.joind.in/v2.1/talk_types/1?verbose=yes
meta:
    count: 1
    total: 1
    this_page: http://api.dev.joind.in/v2.1/talk_types?start=0&resultsperpage=20
~~~~

## Verbose Format

Is exactly the same as the ordinary format.

## Data Fields

The fields available for talk types:

*  ``title``: Name of this talk type
*  ``description``: Description. Currently same as title.

## Hypermedia

*  ``uri``: Where to find this individual record
*  ``verbose_uri``: Where to find a more detailed representation of this record

