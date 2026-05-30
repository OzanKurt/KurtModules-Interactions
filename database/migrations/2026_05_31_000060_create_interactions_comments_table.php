<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained(config('auth.providers.users.table', 'users'))
                ->cascadeOnDelete();
            $table->morphs('commentable');
            $table->foreignId('parent_id')->nullable()->constrained('interactions_comments')->cascadeOnDelete();
            $table->text('body');
            $table->string('status')->default('published');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commentable_type', 'commentable_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions_comments');
    }
};
