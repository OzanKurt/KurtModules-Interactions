<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional read-receipt for mentions, so consumers (e.g. chat) can track which
 * mentions a user has seen. Nullable; null = unseen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interactions_mentions', function (Blueprint $table) {
            $table->timestamp('seen_at')->nullable()->after('mentioned_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('interactions_mentions', function (Blueprint $table) {
            $table->dropColumn('seen_at');
        });
    }
};
