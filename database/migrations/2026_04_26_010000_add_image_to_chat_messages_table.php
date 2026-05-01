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
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('content');
            $table->string('image_name')->nullable()->after('image_path');
            $table->integer('image_size')->nullable()->after('image_name');
            $table->string('mime_type')->nullable()->after('image_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'image_name', 'image_size', 'mime_type']);
        });
    }
};
