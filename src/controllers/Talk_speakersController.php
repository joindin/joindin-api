<?php

class Talk_speakersController extends ApiController
{
    /**
     * This method shall not be needed anympore!
     *
     * It exists only to comply with the base-class!
     *
     * @param Request $request
     * @param PDO     $db
     *
     * @deprecated
     * @throws Exception
     * @return array|bool
     */
    public function handle(Request $request, $db) {}

    /**
     * Get a list of Speakers for a given talk
     *
     * @param Request $request
     * @param PDO     $db
     *
     * @throws Exception
     * @return array|bool
     */
	public function listSpeakersAction(Request $request, $db)
    {
        $talk_id = $this->getItemId($request);

        // verbosity
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        $talkSpeakerMapper = new TalkSpeakerMapper($db, $request);

        $list = $talkSpeakerMapper->getSpeakersByTalkId($talk_id, $resultsperpage, $start, $verbose);

        return $list;
	}


    /**
     * Handle getting a specific speaker for a talk
     *
     * @param Request $request
     * @param PDO $db
     *
     * @return array;
     */
    public function getSpeakerAction($request, $db)
    {
        $speaker_id = $this->getItemId($request);

        $verbose = $this->getVerbosity($request);

        $talkSpeakerMapper = new TalkSpeakerMapper($db, $request);
        $list = $talkSpeakerMapper->getSpeakerById( $speaker_id, $verbose);

        return $list;

    }

}
