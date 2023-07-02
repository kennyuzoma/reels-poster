<?php

namespace App\Models;

use App\Helpers\SiteSettingsHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Tags\HasTags;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    public $fillable = [
        'user_id',
        'service',
        'external_id',
        'username',
        'name',
        'post_time_override',
        'status'
    ];

    public $statuses = [
        1 => 'active',
        2 => 'disconnected',
        3 => 'banned'
    ];

    public $casts = [
        'post_time_override' => 'json',
        'custom_settings' => 'json'
    ];

    public function getDailyPostLimitAttribute()
    {
        $postLimit = $this->custom_settings['daily_post_limit'] ?? null;

        if (!$postLimit) {
            $postLimit = SiteSettingsHelper::get('daily_posts_limit');
        }

        return $postLimit;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function hashtagSets(): HasMany
    {
        return $this->hasMany(HashtagSet::class);
    }

    public function ctas(): HasMany
    {
        return $this->hasMany(Cta::class);
    }

    public function getPostTimeOverrideAttribute()
    {
        return json_decode($this->attributes['post_time_override']);
    }
}
