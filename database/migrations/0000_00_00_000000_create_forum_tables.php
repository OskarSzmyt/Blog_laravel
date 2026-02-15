<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('forum_users', function (Blueprint $table) {
            $table->string('userid')->primary();
            $table->string('username');
            $table->unsignedSmallInteger('userlevel')->default(0);
            $table->string('pass');
        });

        Schema::create('topics', function (Blueprint $table) {
            $table->id('topicid');
            $table->text('topic');
            $table->text('topic_body');
            $table->string('date');
            $table->string('userid');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id('postid');
            $table->text('post');
            $table->string('userid');
            $table->unsignedBigInteger('topicid');
            $table->string('date');
        });

        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('userid');
            $table->unsignedBigInteger('postid')->nullable();
            $table->unsignedBigInteger('topicid');
            $table->string('name');
            $table->string('sufix', 10);
            $table->string('title')->nullable();
            $table->string('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('topics');
        Schema::dropIfExists('forum_users');
    }
};
