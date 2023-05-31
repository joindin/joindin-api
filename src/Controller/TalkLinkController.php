<?php

namespace Joindin\Api\Controller;

use Exception;
use Joindin\Api\Model\TalkMapper;
use PDO;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;

class TalkLinkController extends BaseTalkController
{
    public function getTalkLinks(Request $request, PDO $db): array
    {
        $talk        = $this->getTalkById($request, $db);
        $talk_id     = $talk->ID;
        $talk_mapper = $this->getTalkMapper($db, $request);

        return ['talk_links' => ($talk_mapper->getTalkMediaLinks($talk_id))];
    }

    public function getTalkLink(Request $request, PDO $db): array
    {
        $talk        = $this->getTalkById($request, $db);
        $talk_id     = $talk->ID;
        $talk_mapper = $this->getTalkMapper($db, $request);
        $links       = $talk_mapper->getTalkMediaLinks($talk_id, $request->url_elements[5]);

        if (count($links) !== 1) {
            throw new Exception(
                "ID not found",
                Http::NOT_FOUND
            );
        }

        return $links[0];
    }

    public function updateTalkLink(Request $request, PDO $db): true
    {
        $talk        = $this->getTalkById($request, $db);
        $talk_id     = $talk->ID;
        $talk_mapper = $this->getTalkMapper($db, $request);

        $this->checkAdminOrSpeaker($request, $talk_mapper, $talk_id);

        $link_id      = $request->url_elements[5];
        $display_name = $request->getStringParameter('display_name');
        $url          = $request->getStringParameter('url');

        if (!$talk_mapper->updateTalkLink($talk_id, $link_id, $display_name, $url)) {
            throw new Exception(
                "Update of Link ID Failed",
                Http::INTERNAL_SERVER_ERROR
            );
        }

        $this->sucessfullyAltered($request, $talk_id, $link_id);

        return true;
    }

    public function removeTalkLink(Request $request, PDO $db): true
    {
        $talk        = $this->getTalkById($request, $db);
        $talk_id     = $talk->ID;
        $talk_mapper = $this->getTalkMapper($db, $request);

        $this->checkAdminOrSpeaker($request, $talk_mapper, $talk_id);

        if (!$talk_mapper->removeTalkLink($talk_id, $request->url_elements[5])) {
            throw new Exception(
                "Talk Link ID not found",
                Http::NOT_FOUND
            );
        }

        $this->sucessfullyAltered($request, $talk_id, "");

        return true;
    }

    public function addTalkLink(Request $request, PDO $db): true
    {
        $talk        = $this->getTalkById($request, $db);
        $talk_id     = $talk->ID;
        $talk_mapper = $this->getTalkMapper($db, $request);
        $this->checkAdminOrSpeaker($request, $talk_mapper, $talk_id);

        //Check the content is in the correct format
        $display_name = $request->getStringParameter('display_name');
        $url          = $request->getStringParameter('url');

        if (!$display_name || ! $url) {
            throw new Exception(
                "Missing required fields URL OR Display Name",
                Http::BAD_REQUEST
            );
        }

        $link_id = $talk_mapper->addTalkLink($talk_id, $display_name, $url);

        $this->sucessfullyAltered($request, $talk_id, $link_id);

        return true;
    }

    /**
     * @param Request    $request
     * @param TalkMapper $mapper
     * @param ?int       $talk_id
     *
     * @throws Exception
     */
    protected function checkAdminOrSpeaker(Request $request, TalkMapper $mapper, ?int $talk_id): void
    {
        $is_admin   = $talk_id !== null && $mapper->thisUserHasAdminOn($talk_id);
        $is_speaker = $talk_id !== null && $request->user_id && $mapper->isUserASpeakerOnTalk($talk_id, $request->user_id);

        if (!($is_admin || $is_speaker)) {
            throw new Exception(
                "You do not have permission to add links to this talk",
                Http::FORBIDDEN
            );
        }
    }

    /**
     * @param Request    $request
     * @param int        $talk_id
     * @param int|string $link_id
     */
    protected function sucessfullyAltered(Request $request, int $talk_id, int|string $link_id): void
    {
        $uri = $request->base . '/' . $request->version . '/talks/' . $talk_id . '/links/' . $link_id;

        $view = $request->getView();
        $view->setHeader('Location', rtrim("/", $uri));
        $view->setResponseCode(Http::NO_CONTENT);
    }
}
