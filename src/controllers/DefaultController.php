<?php

class DefaultController extends BaseApiController
{
    public function handle(Request $request, PDO $db)
    {
        // just add the available methods, with links
        return [
            'events'          => $request->base.'/'.$request->version.'/events',
            'hot-events'      => $request->base.'/'.$request->version.'/events?filter=hot',
            'upcoming-events' => $request->base.'/'.$request->version.'/events?filter=upcoming',
            'past-events'     => $request->base.'/'.$request->version.'/events?filter=past',
            'open-cfps'       => $request->base.'/'.$request->version.'/events?filter=cfp',
            'docs'            => 'http://joindin.github.io/joindin-api/',
        ];
    }
}
