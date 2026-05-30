<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions_mentions', function (Blueprint $table) {
            $table->id();
            $table->morphs('mentionable');
            $table->foreignId('mentioned_user_id')
                ->constrained(config('auth.providers.users.table', 'users'))
                ->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['mentionable_type', 'mentionable_id', 'mentioned_user_id'], 'interactions_mentions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions_mentions');
    }
};
