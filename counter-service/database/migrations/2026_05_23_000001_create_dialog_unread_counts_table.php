<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dialog_unread_counts', function (Blueprint $table) {
            $table->id();
            $table->uuid('dialog_id')->index();
            $table->uuid('user_id')->index();
            $table->integer('unread_count')->default(0);
            $table->timestamps();

            $table->unique(['dialog_id', 'user_id']);
            $table->index(['user_id', 'dialog_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialog_unread_counts');
    }
};
