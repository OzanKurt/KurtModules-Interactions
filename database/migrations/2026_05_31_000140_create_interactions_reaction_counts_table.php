<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions_reaction_counts', function (Blueprint $table) {
            $table->id();
            $table->morphs('reactable');
            $table->string('emoji');
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['reactable_type', 'reactable_id', 'emoji']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions_reaction_counts');
    }
};
