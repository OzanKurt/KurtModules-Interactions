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

        Schema::create('interactions_group_members', function (Blueprint $table) use ($users) {
            $table->id();
            $table->foreignId('group_id')->constrained('interactions_groups')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained($users)->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['group_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions_group_members');
    }
};
