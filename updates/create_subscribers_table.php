<?php namespace Flynsarmy\CommentableSubscriptions\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateSubscribersTable extends Migration
{
    public function up()
    {
        Schema::create('flynsarmy_commentable_thread_subscribers', function ($table) {
            $table->integer('thread_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->primary(['thread_id', 'user_id'], 'thread_user_id_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('flynsarmy_commentable_thread_subscribers');
    }
}
