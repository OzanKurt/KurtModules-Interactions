<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained(config('auth.providers.users.table', 'users'))
                ->cascadeOnDelete();
            $table->morphs('subject');
            $table->unsignedTinyInteger('score');
            $table->timestamps();

            $table->unique(['user_id', 'subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions_ratings');
    }
};
