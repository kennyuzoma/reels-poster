<?php

namespace App\Services;

interface ServicePlatform {

    public static function getMediaData($url): array;

    public static function videoUrl($url): string;

    public static function thumbnailUrl($url);

    public static function author($url): string;

    public static function caption($url);

    public static function externalId($url);

}
