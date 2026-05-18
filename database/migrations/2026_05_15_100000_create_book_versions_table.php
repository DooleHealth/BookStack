<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('book_id')->index();
            $table->string('version_label', 100);
            $table->string('version_slug', 150);
            $table->string('book_name');
            $table->text('book_description')->nullable();
            $table->text('book_description_html')->nullable();
            $table->unsignedInteger('created_by')->index();
            $table->timestamps();

            $table->unique(['book_id', 'version_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_versions');
    }
};
