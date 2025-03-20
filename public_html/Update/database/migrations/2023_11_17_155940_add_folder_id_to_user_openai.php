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
        // Add the new column with the default value
        Schema::table('user_openai', function (Blueprint $table) {
            $table->unsignedBigInteger('folder_id')->nullable();
        });

        // Add the foreign key back
        Schema::table('user_openai', function (Blueprint $table) {
            $table->foreign('folder_id')->references('id')->on('folders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key
        Schema::table('user_openai', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
        });

        // Drop the new column
        Schema::table('user_openai', function (Blueprint $table) {
            $table->dropColumn('folder_id');
        });

        // Add the foreign key back
        Schema::table('user_openai', function (Blueprint $table) {
            $table->foreign('folder_id')->references('id')->on('folders')->onDelete('set null');
        });
    }
};
