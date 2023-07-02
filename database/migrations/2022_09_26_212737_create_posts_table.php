<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            $table->string('source_service');
            $table->string('type');
            $table->unsignedBigInteger('external_id');
            $table->string('author')->nullable();
            $table->text('caption')->nullable();
            $table->text('original_public_url');
            $table->text('video_url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->text('hashtags')->nullable();
            $table->text('original_caption');
            $table->boolean('downloaded')->default(0)->nullable();
            $table->boolean('hide_author')->default(0);
            $table->boolean('hide_author_tag')->default(0);
            $table->json('metadata')->nullable();

            $table->timestamp('post_at')->nullable();
            $table->timestamp('posted_at')->nullable();

            $table->integer('status')->default(1);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
};
