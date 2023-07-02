<?php

namespace App\Models;

class Tag extends \Spatie\Tags\Tag {

    public $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

}
