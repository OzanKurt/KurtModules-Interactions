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

        Schema::create('interactions_groups', function (Blueprint $table) use ($users) {
            $table->id();
            $table->foreignId('user_id')->constrained($users)->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions_groups');
    }
};
