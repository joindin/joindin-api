<?php

class DefaultController extends ApiController {
	
    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $db) {
        $retval = array();

        // just add the available methods, with links
        $retval['events'] = $request->base . '/' . $request->version . '/events';
        $retval['hot-events'] = $request->base . '/' . $request->version . '/events?filter=hot';
        $retval['upcoming-events'] = $request->base . '/' . $request->version . '/events?filter=upcoming';
        $retval['past-events'] = $request->base . '/' . $request->version . '/events?filter=past';
        $retval['open-cfps'] = $request->base . '/' . $request->version . '/events?filter=cfp';
        $retval['docs'] = 'http://joindin.github.io/joindin-api/';

        return $retval;
	}
}
