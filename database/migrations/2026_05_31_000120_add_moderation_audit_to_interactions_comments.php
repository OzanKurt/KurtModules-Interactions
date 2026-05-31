<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records who moderated a comment and when. Plain nullable columns (no DB-level
 * foreign key) so the alter is portable across SQLite and MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interactions_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('moderated_by')->nullable()->after('status');
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
        });
    }

    public function down(): void
    {
        Schema::table('interactions_comments', function (Blueprint $table) {
            $table->dropColumn(['moderated_by', 'moderated_at']);
        });
    }
};
