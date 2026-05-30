<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $users = config('auth.providers.users.table', 'users');

        Schema::create('interactions_friendships', function (Blueprint $table) use ($users) {
            $table->id();
            $table->foreignId('sender_id')->constrained($users)->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained($users)->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['sender_id', 'recipient_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions_friendships');
    }
};
