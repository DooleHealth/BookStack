<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_version_chapters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_version_id')->index();
            $table->unsignedInteger('original_chapter_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('description_html')->nullable();
            $table->integer('priority')->default(0);

            $table->foreign('book_version_id')
                ->references('id')->on('book_versions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_version_chapters');
    }
};
