<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_version_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_version_id')->index();
            $table->unsignedBigInteger('book_version_chapter_id')->nullable()->index();
            $table->unsignedInteger('original_page_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->longText('html');
            $table->longText('markdown')->nullable();
            $table->integer('priority')->default(0);

            $table->foreign('book_version_id')
                ->references('id')->on('book_versions')
                ->onDelete('cascade');

            $table->foreign('book_version_chapter_id')
                ->references('id')->on('book_version_chapters')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_version_pages');
    }
};
