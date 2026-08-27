<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('rider_id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('rider_id')->nullable()->after('restaurant_id');
        });

        Schema::dropIfExists('rider_vehicles');
        Schema::dropIfExists('riders');
    }

    public function down(): void
    {
        Schema::create('riders', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('rider_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rider_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('license')->unique();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('rider_id');
            $table->foreignId('rider_id')->nullable()->constrained()->nullOnDelete();
        });
    }
};
