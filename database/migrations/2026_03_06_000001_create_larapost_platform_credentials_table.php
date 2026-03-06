<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('larapost_platform_credentials', function (Blueprint $table): void {
            $table->id();
            $table->string('platform', 50)->unique();
            $table->longText('credentials')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('larapost_platform_credentials');
    }
};
