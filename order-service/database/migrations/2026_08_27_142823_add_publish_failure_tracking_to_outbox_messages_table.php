<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('outbox_messages', function (Blueprint $table): void {
            $table->unsignedInteger('publish_attempts')->default(0)->after('published_at');
            $table->text('last_publish_error')->nullable()->after('publish_attempts');
            $table->timestamp('last_publish_failed_at')->nullable()->index()->after('last_publish_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outbox_messages', function (Blueprint $table): void {
            $table->dropColumn(['publish_attempts', 'last_publish_error', 'last_publish_failed_at']);
        });
    }
};
