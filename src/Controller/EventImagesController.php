<?php

namespace Joindin\Api\Controller;

use _HumbugBoxf43f7c5c5350\Nette\Iterators\Mapper;
use Exception;
use Joindin\Api\Factory\MapperFactory;
use Joindin\Api\Model\EventMapper;
use PDO;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;

class EventImagesController extends BaseApiController
{
    public function __construct(array $config = [], MapperFactory $mapperFactory = null)
    {
        parent::__construct($config);
        $this->mapperFactory = $mapperFactory ?? new MapperFactory();
    }

    public function listImages(Request $request, PDO $db)
    {
        $event_id = $this->getItemId($request);

        $event_mapper = $this->mapperFactory->getMapper(EventMapper::class, $db, $request);

        return ['images' => $event_mapper->getImages($event_id)];
    }

    public function createImage(Request $request, PDO $db)
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", Http::UNAUTHORIZED);
        }

        $event_id = $this->getItemId($request);

        $event_mapper = $this->mapperFactory->getMapper(EventMapper::class, $db, $request);

        // ensure event exists
        $existing_event = $event_mapper->getEventById($event_id, false, true);

        if ($existing_event['meta']['count'] == 0) {
            throw new Exception('There is no event with ID ' . $event_id);
        }

        if (!$event_mapper->thisUserHasAdminOn($event_id)) {
            throw new Exception("You don't have permission to do that", Http::FORBIDDEN);
        }

        if (!isset($_FILES['image'])) {
            throw new Exception("Image was not supplied", Http::BAD_REQUEST);
        }

        if ($_FILES['image']['error'] != 0) {
            throw new Exception("Image upload failed (Code: " . $_FILES['image']['error'] . ")", Http::BAD_REQUEST);
        }

        // check the file meets our expectations
        $uploaded_name = $_FILES['image']['tmp_name'];

        list($width, $height, $filetype) = getimagesize($uploaded_name);

        // must be gif, jpg or png
        if (!in_array($filetype, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            throw new Exception("Supplied image must be a PNG, JPG or GIF", Http::BAD_REQUEST);
        }

        // must be square
        if ($width != $height) {
            throw new Exception("Supplied image must be square", Http::BAD_REQUEST);
        }

        // 140px min, 1440px max
        if ($width < 140) {
            throw new Exception("Supplied image must be at least 140px square", Http::BAD_REQUEST);
        }

        if ($width > 1440) {
            throw new Exception("Supplied image must be no more than 1440px square", Http::BAD_REQUEST);
        }

        // save the file - overwrite current one if there is one
        $extensions[IMAGETYPE_GIF]  = '.gif';
        $extensions[IMAGETYPE_JPEG] = '.jpg';
        $extensions[IMAGETYPE_PNG]  = '.png';
        $saved_filename             = 'icon-' . $event_id . '-orig' . $extensions[$filetype];
        $event_image_path           = $request->getConfigValue('event_image_path');
        $result                     = $request->moveUploadedFile($uploaded_name, $event_image_path . $saved_filename);

        if (false === $result) {
            throw new Exception("The file could not be saved");
        }

        // remove old images from database table and record that we saved the file (this is the orig size)
        $event_mapper->removeImages($event_id);
        $event_mapper->saveNewImage($event_id, $saved_filename, $width, $height, "orig");

        // small is 140px square
        $orig_image = imagecreatefromstring(file_get_contents($event_image_path . $saved_filename));
        imagealphablending($orig_image, false);
        imagesavealpha($orig_image, true);
        $small_width  = 140;
        $small_height = 140;

        $small_image = imagecreatetruecolor($small_width, $small_height);
        imagealphablending($small_image, false);
        imagesavealpha($small_image, true);
        imagecopyresampled($small_image, $orig_image, 0, 0, 0, 0, $small_width, $small_height, $width, $height);

        $small_filename = str_replace('orig', 'small', $saved_filename);

        if ($filetype == IMG_JPG) {
            imagejpeg($small_image, $event_image_path . $small_filename);
        } elseif ($filetype == IMG_GIF) {
            imagegif($small_image, $event_image_path . $small_filename);
        } else {
            imagepng($small_image, $event_image_path . $small_filename);
        }

        $event_mapper->saveNewImage($event_id, $small_filename, $small_width, $small_height, "small");

        $location = $request->base . '/' . $request->version . '/events/' . $event_id;

        $view = $request->getView();
        $view->setHeader('Location', $location);
        $view->setResponseCode(Http::CREATED);
    }

    public function deleteImage(Request $request, PDO $db)
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", Http::UNAUTHORIZED);
        }

        $event_id     = $this->getItemId($request);
        $event_mapper = $this->mapperFactory->getMapper(EventMapper::class, $db, $request);
        $event_mapper->removeImages($event_id);

        $location = $request->base . '/' . $request->version . '/events/' . $event_id;

        $view = $request->getView();
        $view->setHeader('Location', $location);
        $view->setResponseCode(Http::NO_CONTENT);
    }
}
