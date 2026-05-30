<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions_counters', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->string('type');
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['subject_type', 'subject_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions_counters');
    }
};
