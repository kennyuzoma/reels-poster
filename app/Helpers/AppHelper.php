<?php

namespace App\Helpers;

use App\Services\Instagram\Instagram;

class AppHelper {

    public static function determinePostType($url)
    {
        $type = 'reels';

        if (str_contains($url, 'insta')) {
            $mediaData = Instagram::getMediaData($url);

            if (isset($mediaData['carousel_media'])) {
                $type = 'carousel';
            } else {
                if (!isset($mediaData['video_versions']) && isset($mediaData['image_versions2'])) {
                    $type = 'photo';
                }
            }
        }

        return $type;
    }

}
