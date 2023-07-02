<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings Path
    |--------------------------------------------------------------------------
    |
    | Path to the JSON file where settings are stored.
    |
    */

    'path' => storage_path('app/settings.json'),

    /*
    |--------------------------------------------------------------------------
    | Sidebar Label
    |--------------------------------------------------------------------------
    |
    | The text that Nova displays for this tool in the navigation sidebar.
    |
    */

    'sidebar-label' => 'Settings',

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | The browser/meta page title for the tool.
    |
    */

    'title' => 'Settings',

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | The good stuff :). Each setting defined here will render a field in the
    | tool. The only required key is `key`, other available keys include `type`,
    | `label`, `help`, `placeholder`, `language`, and `panel`.
    |
    */

    'settings' => [
        [
            'key' => 'instagram_session_id',
            'label' => 'Instagram Session ID',
            'type' => 'text',
            'help' => 'An Instagram session ID that allows us to query their endpoints',
            'required' => true
        ],
        [
            'key' => 'enable_scheduler_posting',
            'label' => 'Enable Posting Globally via scheduler',
            'type' => 'toggle',
            'help' => 'Enables or Disables (pauses) posting',
            'default' => 'true',
        ],
        [
            'key' => 'allow_duplicate_posts',
            'label' => 'Allow Duplicate Posts',
            'type' => 'toggle',
            'help' => 'Enables or disables duplcate posts',
        ],
        [
            'key' => 'tag_instagram_authors',
            'label' => 'Tag Instagram Authors',
            'type' => 'toggle',
            'help' => 'Enables or disables automatic tagging of Instagram authors',
        ],
        [
            'key' => 'instagram_caption_template',
            'label' => 'Instagram Caption Template',
            'type' => 'textarea',
            'help' => 'The Default caption template',
        ],
        [
            'key' => 'global_hashtag_limit',
            'label' => 'Maximum hashtag count',
            'type' => 'number',
            'help' => 'Maximum number of hashtags a post can have',
            'required' => true
        ],
        [
            'key' => 'app_timezone',
            'label' => 'App Timezone',
            'type' => 'select',
            'options' => [
                'America/Los_Angeles' => 'America/Los_Angeles'
            ],
            'help' => 'The app default timezone. Note: This is NOT related to the timezone option present in the config/app.php file which is set to UTC as default',
            'required' => true
        ],
        [
            'key' => 'daily_posts_limit',
            'label' => 'Posts per day',
            'type' => 'number',
            'help' => 'Number of posts per day',
            'required' => true
        ],
        [
            'key' => 'time_1',
            'label' => 'Post Timeslot 1',
            'type' => 'text',
            'help' => 'Post Timeslot 1',
            'required' => true
        ],
        [
            'key' => 'time_2',
            'label' => 'Post Timeslot 2',
            'type' => 'text',
            'help' => 'Post Timeslot 2',
            'required' => true
        ],
        [
            'key' => 'time_3',
            'label' => 'Post Timeslot 3',
            'type' => 'text',
            'help' => 'Post Timeslot 3',
            'required' => true
        ],
        [
            'key' => 'time_4',
            'label' => 'Post Timeslot 4',
            'type' => 'text',
            'help' => 'Post Timeslot 4',
        ],
        [
            'key' => 'time_5',
            'label' => 'Post Timeslot 5',
            'type' => 'text',
            'help' => 'Post Timeslot 5',
        ]
    ],

];
