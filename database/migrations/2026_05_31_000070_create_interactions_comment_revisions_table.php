<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions_comment_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('interactions_comments')->cascadeOnDelete();
            $table->text('body');
            $table->foreignId('edited_by')
                ->nullable()
                ->constrained(config('auth.providers.users.table', 'users'))
                ->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions_comment_revisions');
    }
};
