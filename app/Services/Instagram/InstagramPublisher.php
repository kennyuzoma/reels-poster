<?php

namespace App\Services\Instagram;

use App\Helpers\ConsoleMessage;
use App\Helpers\GeneralHelper;
use App\Helpers\SiteSettingsHelper;
use App\Models\Post;
use App\Services\Facebook\FacebookApi;
use Illuminate\Support\Facades\Log;

class InstagramPublisher {

    private $fbApi;

    private $consoleMessage;

    private $uploadStatus;

    private $publishedMediaId;

    private Post $post;

    public function __construct()
    {
        $this->consoleMessage = new ConsoleMessage();
        $this->fbApi = new FacebookApi();

    }

    public function setPost(Post $post)
    {
        $this->post = $post;

        // todo move this
        $this->fbApi->setDefaultAccessToken($post->account->user->identities()->where('provider_name', 'facebook')->first()->access_token);

        return $this;
    }

    public function video()
    {
        $this->logger('Begin Processing Video', 'info', true);

        try {
            $this->beginProcessing();

            // begin uploading to API
            $creationId = $this->uploadAndCreateItemContainer([
                'video_url' => $this->post->getFirstMediaUrl('video'),
                'media_type' => 'VIDEO'
            ]);

            // polls facebook API to see if there were any issues during upload
            $this->waitForFinishedStatusOnInstagram($creationId);

            // The rest of the steps
            $this->publishMedia($creationId);
            $this->addMetadataToPost($creationId);
        } catch (\Exception $e) {
            $this->rollback('video');
            throw $e;
        }

        $this->markAsPublished();
        $this->cleanupMedia('video');

        $this->logger('Done Posting Video.. Success!');

        return true;
    }

    public function reels()
    {
        $this->logger('Begin Processing Reel', 'info', true);

        try {
            $this->beginProcessing();

            // begin uploading to API
            $creationId = $this->uploadAndCreateItemContainer([
                'video_url' => $this->post->getFirstMediaUrl('reels'),
                'media_type' => 'REELS',
            ]);

            // polls facebook API to see if there were any issues during upload
            $this->waitForFinishedStatusOnInstagram($creationId);

            // The rest of the steps
            $this->publishMedia($creationId);
            $this->addMetadataToPost($creationId);
        } catch (\Exception $e) {
            $this->rollback('reels');
            throw $e;
        }

        $this->markAsPublished();
        $this->cleanupMedia('reels');

        $this->logger('Done Posting Reel.. Success!');

        return true;
    }

    public function carousel()
    {
        $this->logger('Begin Processing Carousel', 'info', true);

        try {
            $this->beginProcessing();

            $creationIds = [];

            // begin uploading to API
            foreach ($this->post->getMedia('carousel') as $media) {
                if ($media['mime_type'] == 'video/mp4') {
                    $creationId = $this->uploadAndCreateItemContainer([
                        'is_carousel_item' => TRUE,
                        'video_url' => $media->getFullUrl(),
                        'media_type' => 'VIDEO'
                    ]);
                } else {
                    $creationId = $this->uploadAndCreateItemContainer([
                        'is_carousel_item' => TRUE,
                        'image_url' => $media->getFullUrl()
                    ]);
                }

                $creationIds[] = $creationId;
            }

            // poll facebook API to see if there were any issues during EACH upload
            foreach ($creationIds as $position => $creationId) {
                $this->logger('Checking Media Item container ' . $creationId . ' upload status (' . $position + 1 . '/' . count($creationIds) . ')');
                $this->waitForFinishedStatusOnInstagram($creationId);
                $this->logger('Item container ' . $creationId . ' upload SUCCESSFUL!');
                $this->logger(' ');
            }

            $this->logger('------');
            $this->logger('Creation Ids ... ' . implode(',', $creationIds));
            $this->logger('------');

            // create carousel container
            $carouselCreationId = $this->fbApi->post($this->post->account->external_id . '/media', [
                'media_type' => 'CAROUSEL',
                'children' => $creationIds,
                'caption' => $this->caption()
            ])['id'];

            $this->logger('Checking Carousel Container ' . $carouselCreationId . ' upload status');
            $this->waitForFinishedStatusOnInstagram($carouselCreationId);
            $this->logger('Carousel container ' . $carouselCreationId . ' upload SUCCESSFUL!');

            // The rest of the steps
            $this->publishMedia($carouselCreationId);
            $this->addMetadataToPost($creationIds);
        } catch (\Exception $e) {
            $this->rollback('carousel');
            throw $e;
        }

        $this->markAsPublished();
        $this->cleanupMedia('carousel');

        $this->logger('Done Posting Carousel.. Success!');

        return true;
    }

    public function photo()
    {
        $this->logger('Begin Processing Photo', 'info', true);

        try {
            // begin uploading to API
            $creationId = $this->uploadAndCreateItemContainer([
                'image_url' => $this->post->getFirstMediaUrl('photo')
            ]);

            // polls facebook API to see if there were any issues during upload
            $this->waitForFinishedStatusOnInstagram($creationId, 'image');

            // The rest of the steps
            $this->publishMedia($creationId);
            $this->addMetadataToPost($creationId);
        } catch (\Exception $e) {
            $this->rollback('photo');
            throw $e;
        }
        $this->markAsPublished();
        $this->cleanupMedia('photo');

        $this->logger('Done Posting Photo.. Success!');

        return true;
    }

    public function caption(): string
    {
        if (!is_null($this->post->sponsor_id)) {
            $caption = $this->buildSponsoredCaption();
        } else {
            $caption = $this->buildRegularCaption();
        }

        return $caption;
    }

    private function buildSponsoredCaption(): string
    {
        return GeneralHelper::trimWhitespacePerLine($this->post->caption);
    }

    private function buildRegularCaption()
    {
        $data['action'] = $this->post->type == 'photo' ? '🎥' : '📷';

        $data['caption'] = $this->post->caption;
        if ($this->post->cta) {
            $data['caption'] .= ' ' . $this->post->cta->content;
        }

        $data['hashtags'] = $this->post->hashtags;
        $data['username'] = $this->post->account->username;
        $data['author'] = $this->post->author_generated;

        // check if there is a template override
        $caption = SiteSettingsHelper::get('instagram_caption_template');

        if (!is_null($this->post->account->caption_template_override)) {
            $caption = $this->post->account->caption_template_override;
        }

        $caption = GeneralHelper::trimWhitespacePerLine($caption);

        if (!is_null($caption)) {
            foreach ($data as $field => $value) {
                $caption = str_replace("{{" . $field . "}}", $value, $caption);
            }

            if ($this->post->hide_author) {
                $caption = GeneralHelper::replaceBetween($caption, "{{ START_AUTHOR_SECTION }}\n", "{{ END_AUTHOR_SECTION }}\n", '');
            }

            $caption = str_replace("{{ START_AUTHOR_SECTION }}\n", '', $caption);
            $caption = str_replace("{{ END_AUTHOR_SECTION }}\n", '', $caption);

        } else {
            $caption = $this->defaultCaption();
        }

        return $caption;
    }

    private function defaultCaption(): string
    {
        $action = '🎥';

        if ($this->post->type == 'photo') {
            $action = '📷';
        }

        $caption = $this->post->caption;

        $caption .= "\r\n\r\n";

        if ($this->post->source_service == 'instagram') {
            $caption .= $action . ': ';

            if (SiteSettingsHelper::get('tag_instagram_authors')) {
                $caption .= '@';
            }

            $caption .= $this->post->author;
        } else {
            $caption .= '🎥: ' . $this->post->author . ' (TikTok)';
        }

        $caption .= "\r\n\r\n";
        $caption .= $this->post->hashtags;

        return $caption;
    }

    private function logger($message, $type = 'info', $begin = false)
    {
        if ($begin) {
            $this->consoleMessage->newLine(1);
        }

        $this->consoleMessage->{$type}($message);
    }

    private function publishMedia($creationId)
    {
        $this->logger('Publishing Media...');

        $mediaPublishRequest = $this->fbApi->post($this->post->account->external_id . '/media_publish', [
            'creation_id' => $creationId,
        ]);

        $this->publishedMediaId = $mediaPublishRequest['id'];

        $this->logger('Media Published!');

        return $mediaPublishRequest;
    }

    private function uploadAndCreateItemContainer($postArr)
    {
        $loggerText = 'Uploading ';
        if (!isset($postArr['media_type'])) {
            $loggerText .= 'image ';
        }
        if (isset($postArr['media_type']) && $postArr['media_type'] == 'REELS') {
            $loggerText .= 'reels ';
        }
        if (isset($postArr['media_type']) && $postArr['media_type'] == 'VIDEO') {
            $loggerText .= 'video ';
        }

        $loggerText .= 'media...';
        $this->logger($loggerText);

        $postArr['caption'] = $this->caption();

        $postRequest = $this->fbApi->post($this->post->account->external_id . '/media', $postArr);

        return $postRequest['id'];

    }

    private function addMetadataToPost($creationId)
    {
        $this->post->metadata = [
            'instagram' => [
                'ig_business_account_id' => $this->post->account->external_id,
                'creation_id' => $creationId,
                'shortcode' => $this->fbApi->get($this->publishedMediaId . '?fields=shortcode')['shortcode']
            ]
        ];
    }

    private function markAsPublished()
    {
        $this->post->status = Post::$statuses['published'];
        $this->post->posted_at = now();
        $this->post->save();
    }

    /**
     * @throws \Exception
     */
    private function waitForFinishedStatusOnInstagram($creationId, $type = 'video')
    {
        $this->logger('Poll Instagram for Status');
        $this->consoleMessage->outputStyle->progressStart(5);

        do {
            if ($type == 'image') {
                sleep(1);
            } else {
                sleep(5);
            }

            $statusInfo = $this->fbApi->get($creationId . '?fields=id,status,status_code');
            $this->uploadStatus = $statusInfo['status_code'];

            if (isset($statusInfo['error']) || $statusInfo['status_code'] == 'ERROR') {
                Log::error($statusInfo);
                throw new \Exception('There was an error processing the media on IG\'s side: ' . serialize($statusInfo));
            }

            $this->consoleMessage->outputStyle->progressAdvance();
        } while ($this->uploadStatus != 'FINISHED');

        $this->consoleMessage->outputStyle->progressFinish();
        $this->logger('Finish Polling');
    }

    private function rollback($type)
    {
        $this->post->status = Post::$statuses['failed'];
        $this->post->save();
    }

    private function beginProcessing()
    {
        $this->post->status = Post::$statuses['processing'];
        $this->post->save();
    }

    private function cleanupMedia($type)
    {
        $this->logger('Clean up files...');
        $this->post->clearMediaCollection($type);
    }

}
