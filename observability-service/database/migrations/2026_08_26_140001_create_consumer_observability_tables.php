<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumer_incidents', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id', 64)->unique();
            $table->string('service')->index();
            $table->string('outcome')->index();
            $table->string('source_event_id', 64)->nullable();
            $table->string('source_event_type')->nullable();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedInteger('retry_count')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('consumer_health', function (Blueprint $table): void {
            $table->id();
            $table->string('service')->unique();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumer_health');
        Schema::dropIfExists('consumer_incidents');
    }
};
