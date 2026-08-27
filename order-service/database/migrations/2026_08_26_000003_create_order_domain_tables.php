<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('item_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['restaurant_id', 'name']);
        });

        Schema::create('restaurant_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('item_price');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

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

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('client_name');
            $table->string('client_phone')->nullable();
            $table->string('client_email')->nullable();
            $table->text('delivery_address');
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('order_price');
            $table->unsignedInteger('delivery_fee');
            $table->unsignedInteger('total_price');
            $table->foreignId('rider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->index();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_item_id')->constrained()->restrictOnDelete();
            $table->string('item_name');
            $table->unsignedInteger('item_price');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('total_price');
            $table->timestamps();
        });

        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->unsignedSmallInteger('event_version');
            $table->json('payload');
            $table->timestamp('occurred_at');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['published_at', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('rider_vehicles');
        Schema::dropIfExists('riders');
        Schema::dropIfExists('restaurant_items');
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('restaurants');
    }
};
