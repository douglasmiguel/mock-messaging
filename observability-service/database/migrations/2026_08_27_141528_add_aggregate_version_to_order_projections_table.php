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
        Schema::table('order_projections', function (Blueprint $table): void {
            $table->unsignedBigInteger('aggregate_version')->default(0)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_projections', function (Blueprint $table): void {
            $table->dropColumn('aggregate_version');
        });
    }
};
