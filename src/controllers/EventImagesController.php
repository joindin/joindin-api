<?php

class EventImagesController extends ApiController
{
    public function createImage($request, $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", 400);
        }

        $event_id = $this->getItemId($request);

        $event_mapper = new EventMapper($db, $request);

        // ensure event exists
        $existing_event = $event_mapper->getEventById($event_id, false, true);
        if ($existing_event['meta']['count'] == 0) {
            throw new Exception('There is no event with ID ' . $event_id);
        }

        if (!$event_mapper->thisUserHasAdminOn($event_id)) {
            throw new Exception("You don't have permission to do that", 403);
        }

        if (!isset($_FILES['image'])) {
            throw new Exception("Image was not supplied", 400);
        }

        if ($_FILES['image']['error'] != 0) {
            throw new Exception("Image upload failed (Code: " . $FILES['image']['error'] . ")", 400);
        }

        // check the file meets our expectations
        $uploaded_name = $_FILES['image']['tmp_name'];

        list($width, $height, $filetype) = getimagesize($uploaded_name);

        // must be gif, jpg or png
        if (!in_array($filetype, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            throw new Exception("Supplied image must be a PNG, JPG or GIF", 400);
        }

        // must be square
        if ($width != $height) {
            throw new Exception("Supplied image must be square", 400);
        }

        // 140px min, 1440px max
        if ($width < 140) {
            throw new Exception("Supplied image must be at least 140px square", 400);
        }
        if ($width > 1440) {
            throw new Exception("Supplied image must be no more than 1440px square", 400);
        }

        // save the file - overwrite current one if there is one
        $extensions[IMAGETYPE_GIF] = '.gif';
        $extensions[IMAGETYPE_JPEG] = '.jpg';
        $extensions[IMAGETYPE_PNG] = '.png';
        $saved_filename = 'icon-' . $event_id . '-orig' . $extensions[$filetype];
        $event_image_path = $request->getConfigValue('event_image_path');
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 500, $severity, $file, $line);
        });
        $result = move_uploaded_file($uploaded_name, $event_image_path . $saved_filename);
        restore_error_handler();

        if (false === $result) {
            throw new Exception("The file could not be saved");
        }

        // remove old images from database table and record that we saved the file (this is the orig size)
        $event_mapper->removeImages($event_id);
        $event_mapper->saveNewImage($event_id, $saved_filename, $width, $height, "orig");

        // small is 140px square
        $orig_image = imagecreatefromstring(file_get_contents($event_image_path . $saved_filename));
        $small_width = 140;
        $small_height = 140;

        $small_image = imagecreatetruecolor($small_width, $small_height);
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
        header('Location: ' . $location, null, 201);
    }

    public function deleteImage($request, $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", 400);
        }

        $event_id = $this->getItemId($request);
        $event_mapper = new EventMapper($db, $request);
        $event_mapper->removeImages($event_id);

        $location = $request->base . '/' . $request->version . '/events/' . $event_id;
        header('Location: ' . $location, null, 204);
        exit;
    }
}
