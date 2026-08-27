<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id', 64);
            $table->string('recipient');
            $table->string('template');
            $table->string('subject');
            $table->string('title');
            $table->text('message');
            $table->string('action_label');
            $table->string('action_url');
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['event_id', 'recipient', 'template']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
