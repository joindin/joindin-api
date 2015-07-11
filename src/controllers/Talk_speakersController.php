<?php

class Talk_speakersController extends ApiController
{

    public function handle(Request $request, $db)
    {
        // TODO: Implement handle() method.
    }

    /**
     * Handle getting a list of speakers of a talk
     *
     * @param Request $request
     * @param PDO $db
     *
     * @return array
     */
    public function listAction($request, $db)
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
    public function getAction($request, $db)
    {
        $speaker_id = $this->getItemId($request);

        $verbose = $this->getVerbosity($request);

        $talkSpeakerMapper = new TalkSpeakerMapper($db, $request);

        $list = $talkSpeakerMapper->getSpeakerById( $speaker_id, $verbose);

        return $list;

    }


    /**
     * Handle adding a user to a talk
     *
     * @param Request $request
     * @param PDO $db
     *
     * @return void
     */
    public function postAction($request, $db)
    {
        if(!isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", 400);
        }

        $talk_id = $this->getItemId($request);

        $talk_mapper = new TalkMapper($db, $request);

        $talk_list = $talk_mapper->getTalkById($talk_id);
        if (count($talk_list['talks']) < 1) {
            throw new Exception('Talk not found', 400);
        }

        if (! $talk_mapper->thisUserHasAdminOn($talk_id)) {
            throw new Exception('You do not have permissions to add a talk to this event', 403);
        }

        $speaker_name = filter_var(
            $request->getParameter('speaker_name'),
            FILTER_SANITIZE_STRING
        );

        $talkSpeakerMapper = new TalkSpeakerMapper($db, $request);
        $new_id = $talkSpeakerMapper->createSpeaker(array(
            'speaker_name' => $speaker_name,
            'talk_id' => $talk_id,
        ));

        $uri = $request->base . '/' . $request->version . '/speakers/' . $new_id;
        header("Location: " . $uri, true, 201);

        $new_speaker = $talkSpeakerMapper->getSpeakerById($new_id);

        return $new_speaker;
    }

    /**
     * Handle editing a user for a talk
     *
     * @param Request $request
     * @param PDO     $db
     *
     * return void
     */
    public function putAction($request, $db)
    {
        $errors = array();
        if(!isset($request->user_id)) {
            throw new Exception("You must be logged in to edit data", 400);
        }

        $speaker_id = $this->getItemId($request);

        $speaker_mapper = new TalkSpeakerMapper($db, $request);

        $existing_speaker = $speaker_mapper->getSpeakerById($speaker_id, true);
        if (! $existing_speaker || $existing_speaker['meta']['count'] < 1) {
            throw new Exception('The speaker could not be found', 404);
        }
        $existing_speaker = $existing_speaker['speakers'][0];

        $talk_mapper = new TalkMapper($db, $request);
        $isAdmin   = $talk_mapper->thisUserHasAdminOn($speaker_mapper->getTalkIdForSpeaker($speaker_id));

        if (! $isAdmin) {
            throw new Exception('You are not allowed to edit this speaker', 403);
        }

        throw new Exception('This method is not yet fully implemented', 400);

    }

    /**
     * Handle removing user from a talk
     *
     * @param Request $request
     * @param PDO     $db
     *
     * @return void
     */
    public function deleteAction($request, $db)
    {
        if(!isset($request->user_id)) {
            throw new Exception("You must be logged in to delete data", 400);
        }

        $speaker_id = $this->getItemId($request);
        $speaker_mapper = new TalkSpeakerMapper($db, $request);
        $list = $speaker_mapper->getSpeakerById($speaker_id);
        if(false === $list) {
            // talk isn't there so it's as good as deleted
            header("Content-Length: 0", NULL, 204);
            return; // no more content
        }

        $talk_mapper = new TalkMapper($db, $request);
        $is_admin = $talk_mapper->thisUserHasAdminOn($speaker_mapper->getTalkIdForSpeaker($speaker_id));
        if(!$is_admin) {
            throw new Exception("You do not have permission to do that", 403);
        }

        $speaker_mapper->deleteSpeaker($speaker_id);
        header("Content-Length: 0", NULL, 204);

        return true;
    }

}