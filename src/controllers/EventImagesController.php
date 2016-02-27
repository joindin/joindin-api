<?php

class EventImagesController extends ApiController
{
    public function createImage($request, $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", 400);
        }

        $event_id = $this->getItemId($request);

        if (!isset($_FILES['image'])) {
            throw new Exception("Image was not supplied", 400);
        }

        if ($_FILES['image']['error'] != 0) {
            throw new Exception("Image upload failed (Code: " . $FILES['image']['error'] . ")", 400);
        }

        // check the file meets our expectations
        $uploaded_name = $_FILES['image']['tmp_name'];

        list($width, $height, $filetype) = getimagesize($uploaded_name);

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

        // save the file
        $filename = $_FILES['image']['name'];
        $event_image_path = $request->getConfigValue('event_image_path');
        $savedFilename = $this->moveFile($uploaded_name, $filename, $event_image_path);

        if (false === $savedFilename) {
            throw new Exception("The file could not be saved");
        }

        // remove old images; record that we saved the file (this is the orig size)
        $event_mapper = new EventMapper($db, $request);
        $event_mapper->removeImages($event_id);
        $event_mapper->saveNewImage($event_id, $savedFilename, $width, $height, "orig");

        // small is 140px square
        $orig_image = imagecreatefromstring(file_get_contents($event_image_path . $savedFilename));
        $small_width = 140;
        $small_height = 140;

        $small_image = imagecreatetruecolor($small_width, $small_height);
        imagecopyresampled($small_image, $orig_image, 0, 0, 0, 0, $small_width, $small_height, $width, $height);

        $small_filename = str_replace('orig', 'small', $savedFilename);

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

    protected function moveFile($uploaded_name, $filename, $path)
    {
        $filename_pieces = explode(".", $filename);
        $newfilename = $filename_pieces[0] . "-orig." . $filename_pieces[1];

        // check if the file exists before moving
        if (!file_exists($path . $newfilename)) {
            move_uploaded_file($uploaded_name, $path . $newfilename);
            return $newfilename;
        } else {
            // we need to avoid a collision
            // try appending numbers but give up after 10
            for ($i = 0; $i < 10; $i++) {
                $newfilename = $filename_pieces[0] . $i . "-orig." . $filename_pieces[1];

                if (!file_exists($path . $newfilename)) {
                    move_uploaded_file($uploaded_name, $path . $newfilename);
                    return $newfilename;
                }
            }
            // at this point, we have given up
            return false;
        }
    }

    public function createImage($request, $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", 400);
        }

        $event_id = $this->getItemId($request);
        $event_mapper = new EventMapper($db, $request);
        $event_mapper->removeImages($event_id);

        $location = $request->base . '/' . $request->version . '/events/' . $event_id;
        header('Location: ' . $location, null, 204);
    }
}
