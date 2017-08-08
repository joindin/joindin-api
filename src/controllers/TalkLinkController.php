<?php

class TalkLinkController extends BaseTalkController
{
    public function getTalkLinks(Request $request, PDO $db)
    {
        $talk = $this->getTalkById($request, $db);
        $talk_id = $talk->ID;
        $talk_mapper = $this->getTalkMapper($db, $request);
        return ['talk_links' => ($talk_mapper->getTalkMediaLinks($talk_id))];
    }

    public function getTalkLink(Request $request, PDO $db)
    {
        $talk = $this->getTalkById($request, $db);
        $talk_id = $talk->ID;
        $talk_mapper = $this->getTalkMapper($db, $request);
        $links = $talk_mapper->getTalkMediaLinks($talk_id, $request->url_elements[5]);

        if (count($links) !== 1) {
            throw new Exception(
                "ID not found",
                404
            );
        }

        return $links[0];
    }

    public function updateTalkLink(Request $request, PDO $db)
    {
        $talk = $this->getTalkById($request, $db);
        $talk_id = $talk->ID;
        $talk_mapper = $this->getTalkMapper($db, $request);

        $this->checkAdminOrSpeaker($request, $talk_mapper, $talk_id);

        $link_id = $request->url_elements[5];
        $display_name = $request->getParameter('display_name');
        $url = $request->getParameter('url');

        if (!$talk_mapper->updateTalkLink($talk_id, $link_id, $display_name, $url)) {
            throw new Exception(
                "Update of Link ID Failed",
                500
            );
        }

        $this->sucessfullyAltered($request, $talk_id, $link_id);

        return true;
    }

    public function removeTalkLink(Request $request, PDO $db)
    {
        $talk = $this->getTalkById($request, $db);
        $talk_id = $talk->ID;
        $talk_mapper = $this->getTalkMapper($db, $request);

        $this->checkAdminOrSpeaker($request, $talk_mapper, $talk_id);

        if (!$talk_mapper->removeTalkLink($talk_id, $request->url_elements[5])) {
            throw new Exception(
                "Talk Link ID not found",
                404
            );
        }

        $this->sucessfullyAltered($request, $talk_id, "");

        return true;
    }

    public function addTalkLink(Request $request, PDO $db)
    {
        $talk = $this->getTalkById($request, $db);
        $talk_id = $talk->ID;
        $talk_mapper = $this->getTalkMapper($db, $request);
        $this->checkAdminOrSpeaker($request, $talk_mapper, $talk_id);

        //Check the content is in the correct format
        $display_name = $request->getParameter('display_name');
        $url = $request->getParameter('url');
        if (!$display_name || !$url) {
            throw new Exception(
                "Missing required fields URL OR Display Name",
                400
            );
        }

        $link_id = $talk_mapper->addTalkLink($talk_id, $display_name, $url);
        if (!$link_id) {
            throw new Exception(
                "The Link has not been inserted",
                400
            );
        }

        $this->sucessfullyAltered($request, $talk_id, $link_id);
        return true;
    }

    /**
     * @param Request $request
     * @param TalkMapper $mapper
     * @param int $talk_id
     * @throws Exception
     */
    protected function checkAdminOrSpeaker(Request $request, TalkMapper $mapper, $talk_id)
    {
        $is_admin = $mapper->thisUserHasAdminOn($talk_id);
        $is_speaker = $mapper->isUserASpeakerOnTalk($talk_id, $request->user_id);
        if (!($is_admin || $is_speaker)) {
            throw new Exception(
                "You do not have permission to add links to this talk",
                403
            );
        }
    }

    /**
     * @param Request $request
     * @param int $talk_id
     * @param int $link_id
     */
    protected function sucessfullyAltered(Request $request, $talk_id, $link_id)
    {
        $uri = $request->base . '/' . $request->version . '/talks/' . $talk_id . '/links/' . $link_id;

        $view = $request->getView();
        $view->setHeader('Location', rtrim("/", $uri));
        $view->setResponseCode(204);
    }
}
