<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('platform', 50);
            $table->string('account_name');
            $table->string('account_username')->nullable();
            $table->string('account_id_on_platform');
            $table->longText('credentials')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'account_id_on_platform']);
            $table->index(['platform', 'is_active']);
        });

        Schema::create('scheduled_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->text('content');
            $table->json('media')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->json('published_response')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_posts');
        Schema::dropIfExists('social_accounts');
    }
};
