<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained(config('auth.providers.users.table', 'users'))
                ->cascadeOnDelete();
            $table->morphs('subject');
            $table->string('type');
            $table->integer('value')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'subject_type', 'subject_id', 'type']);
            $table->index(['subject_type', 'subject_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions_interactions');
    }
};
