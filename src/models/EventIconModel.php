<?php

class EventIconModel
{
    private $eventMapper;
    private $imageDirectory;

    public function __construct(EventMapper $eventMapper, $imageDirectory)
    {
        $this->eventMapper = $eventMapper;
        $this->imageDirectory = $imageDirectory;
    }

    /**
     * create icon from uploaded image data
     *
     * @param string $type       image mime type
     * @param string $imageData  image data
     * @param string $event_id   event id
     *
     * @throws Exception on failure
     */
    public function createFromData($type, $imageData, $event_id)
    {
        // is this a valid image file?
        $imageData = base64_decode($imageData);
        $image = @imagecreatefromstring($imageData);
        if ($image === false) {
            throw new Exception('Unrecognised image', 400);
        }

        // is it square?
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width !== $height) {
            imagedestroy($image);
            throw new Exception('Image is not square', 400);
        }

        // determine extension
        switch ($type) {
            case 'image/png':
                $extension = 'png';
                break;

            case 'image/jpeg':
            case 'image/pjpeg':
                $extension = 'jpg';
                break;

            default:
                throw new Exception('Unrecognised image type. Only jpg or png is acceptable.', 400);
        }

        // create large image
        $filename = "event-logo-$event_id-large.$extension";
        $this->createImageOfSize($filename, 1440, $type, $imageData, $image);

        // create small image
        $filename = "event-logo-$event_id.$extension";
        $this->createImageOfSize($filename, 140, $type, $imageData, $image);

        imagedestroy($image);

        // save filename to event record in database
        $saved = $this->eventMapper->setIconFilename($event_id, $filename);
        if (!$saved) {
            throw new Exception('Failed to update event with icon information', 400);
        }
    }


    /**
     * Create image on disk of a particular size. Note event icons are square,
     * so we only need the width.
     *
     * @param mixed $filename   Filename to store image
     * @param mixed $width      Width of image
     * @param mixed $type       Mime type of image data
     * @param mixed $imageData  Image data
     * @param mixed $image      Image resource
     *
     * @throws Exception on failure
     */
    protected function createImageOfSize($filename, $width, $type, $imageData, $image)
    {
        $path = $this->imageDirectory . $filename;
        $currentWidth = imagesx($image);
        if ($currentWidth < $width) {
            // image is too small to resize - just save it to avoid degradation
            file_put_contents($path, $imageData);
        } else {
            // resize and save
            $newLogo = imagecreatetruecolor($width, $width);
            imagecopyresampled($newLogo, $image, 0, 0, 0, 0, $width, $width, $currentWidth, $currentWidth);

            // save
            switch ($type) {
                case 'image/png':
                    $saved = imagepng($newLogo, $path, 0);
                    break;

                default:
                    // jpeg
                    $saved = imagejpeg($newLogo, $path, 90);
            }
            imagedestroy($newLogo);
            if (!$saved) {
                throw new Exception('Failed to save image', 400);
            }
        }
    }
}
