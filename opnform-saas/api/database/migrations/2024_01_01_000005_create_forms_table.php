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
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('creator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->string('slug', 20)->unique();
            $table->text('description')->nullable();
            $table->enum('visibility', ['public', 'draft', 'closed'])->default('draft');
            $table->json('properties')->nullable(); // Form fields/questions
            $table->string('theme')->default('default');
            $table->json('settings')->nullable();
            $table->json('notifications')->nullable();
            $table->json('integrations')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->text('closed_text')->nullable();
            $table->text('submitted_text')->nullable();
            $table->string('redirect_url')->nullable();
            $table->boolean('use_captcha')->default(false);
            $table->string('password')->nullable();
            $table->string('logo_picture')->nullable();
            $table->string('cover_picture')->nullable();
            $table->text('custom_code')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'visibility']);
            $table->index('slug');
            $table->index('creator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
