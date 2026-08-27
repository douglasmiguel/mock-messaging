<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_snapshots', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('order_id')->unique();
            $t->string('status');
            $t->string('restaurant_name');
            $t->string('restaurant_email');
            $t->string('client_name');
            $t->string('client_email');
            $t->json('payload');
            $t->timestamps();
        });
        Schema::create('action_tokens', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->string('actor');
            $t->string('token', 64)->unique();
            $t->timestamps();
            $t->unique(['order_id', 'actor']);
        });
        Schema::create('processed_events', function (Blueprint $t): void {
            $t->id();
            $t->string('event_id')->unique();
            $t->string('event_type');
            $t->unsignedBigInteger('order_id');
            $t->timestamps();
        });
        Schema::create('issues', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->text('description');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
        Schema::dropIfExists('processed_events');
        Schema::dropIfExists('action_tokens');
        Schema::dropIfExists('order_snapshots');
    }
};
