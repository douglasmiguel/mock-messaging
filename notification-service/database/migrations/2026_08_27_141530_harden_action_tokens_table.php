<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('action_tokens', function (Blueprint $table): void {
            $table->string('token_hash', 64)->nullable()->after('actor');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamp('last_used_at')->nullable();
        });

        DB::table('action_tokens')->orderBy('id')->each(function (object $token): void {
            DB::table('action_tokens')->where('id', $token->id)->update([
                'token_hash' => hash('sha256', $token->token),
                'expires_at' => now()->addHours(24),
            ]);
        });

        Schema::table('action_tokens', function (Blueprint $table): void {
            $table->unique('token_hash');
            $table->dropUnique(['order_id', 'actor']);
            $table->dropUnique(['token']);
            $table->dropColumn('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('action_tokens', function (Blueprint $table): void {
            $table->string('token', 64)->nullable();
            $table->dropUnique(['token_hash']);
            $table->dropColumn(['token_hash', 'expires_at', 'revoked_at', 'last_used_at']);
            $table->unique(['order_id', 'actor']);
        });
    }
};
