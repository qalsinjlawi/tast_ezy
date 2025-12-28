<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('short_description', 500);
            $table->text('description');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('thumbnail');
            $table->string('level', 50);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index('instructor_id');
            $table->index('category_id');
            $table->index('is_published');
            $table->index(['category_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};