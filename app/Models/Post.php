<?php

namespace App\Models;

use App\Helpers\SiteSettingsHelper;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Nova\Actions\Actionable;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;
use Spatie\Tags\Tag;

class Post extends Model implements HasMedia, Sortable
{
    use HasFactory, SoftDeletes, Notifiable, InteractsWithMedia, Actionable, HasTags, SortableTrait;

    public $fillable = [
        'source_service',
        'type',
        'external_id',
        'account_id',
        'author',
        'caption',
        'original_public_url',
        'video_url',
        'thumbnail_url',
        'hashtag_type',
        'raw_hashtags',
        'hashtag_position',
        'original_caption',
        'metadata',
        'utc_post_at',
        'post_at',
        'posted_at',
        'status',
    ];

    public $casts = [
        'metadata' => AsArrayObject::class,
        'utc_post_at' => 'datetime',
        'post_at' => 'datetime',
        'posted_at' => 'datetime',
        'delete_at' => 'datetime',
    ];

    public static $statuses = [
        'ready' => 1,
        'processing' => 2,
        'published' => 3,
        'paused' => 4,
        'failed' => 5,
        'importing' => 6,
        'social_deleted' => 7,
        'draft' => 8
    ];

    public $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
    ];

    public $appends = [
        'author_generated'
    ];

    /**
     * Route notifications for the Slack channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForSlack($notification)
    {
        return config('services.slack.webhook_url');
    }

    public function getHashtagsAttribute()
    {
        $hashtags = null;

        if ($this->hashtag_type == 'set') {
            $hashtags = $this->hashtagSet->tag_list ?? null;

        } elseif ($this->hashtag_type == 'raw') {
            $hashtags = $this->raw_hashtags;

        } elseif ($this->hashtag_type == 'cloud') {
            $hashtags = $this->getCloudHashtags();

        } elseif ($this->hashtag_type == 'set_with_raw') {
            $hashtags = $this->hashtagSet->tag_list;
            if ($this->hashtag_position == 'beginning') {
                $hashtags = $this->raw_hashtags . ' ' . $hashtags;
            } else {
                $hashtags .= ' ' . $this->raw_hashtags;
            }

        } elseif ($this->hashtag_type == 'set_with_cloud') {
            $hashtags = $this->hashtagSet->tag_list;

            if ($this->hashtag_position == 'beginning') {
                $hashtags = $this->getCloudHashtags() . ' ' . $hashtags;
            } else {
                $hashtags .= ' ' . $this->getCloudHashtags();
            }
        }

        if (!empty($hashtags)) {
            $hashtagsArr = explode(' ', $hashtags);

            // remove duplicates
            $hashtagsArr = array_unique($hashtagsArr);

            // enforce hashtag limit
            $hashtagsArr = array_slice($hashtagsArr, 0, SiteSettingsHelper::get('global_hashtag_limit'));

            $hashtags = implode(' ', $hashtagsArr);
        }

        return $hashtags;
    }

    public function getCloudHashtags()
    {
        $return = null;
        if (!$this->tags->isEmpty()) {
            // from the posts tags
            $return = implode(' ', $this->tags->map(function (Tag $tag) {
                return '#' . $tag->name;
            })->values()->toArray());
        }

        return $return;
    }

    public function getHashtagMode()
    {
        if (isset($this->hashtagSet)) {
            $hashtagMode = 'from_account';
        } elseif (!$this->tags->isEmpty()) {
            $hashtagMode = 'custom';
        } elseif (isset($this->hashtags_raw['hashtags'])) {
            if (isset($this->hashtags_raw['additional'])) {
                $hashtagMode = 'hashtag_set_with_additional_hashtags';
            } else {
                $hashtagMode = 'hashtags_raw';
            };
        } else {
            $hashtagMode = null;
        }

        return $hashtagMode;
    }

    public function getAuthorGeneratedAttribute()
    {
        $author = $this->attributes['author'];

        if (!$author) {
            $author = 'DM for credit';
        } else {

            if ($this->source_service == 'tiktok') {
                $author .= ' (TikTok)';
            }

            if (
                $this->source_service == 'instagram'
                && $this->hide_author_tag == 0
                && SiteSettingsHelper::get('tag_instagram_authors')
            ) {
                $author = '@' . $author;
            }
        }

        return $author;
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    public function hashtagSet(): BelongsTo
    {
        return $this->belongsTo(HashtagSet::class);
    }

    public function cta(): BelongsTo
    {
        return $this->belongsTo(Cta::class)->withTrashed();
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class)->withTrashed();
    }
}
