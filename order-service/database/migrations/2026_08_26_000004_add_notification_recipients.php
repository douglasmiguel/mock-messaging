<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::table('restaurants', function (Blueprint $table): void {
            $table->string('email')->nullable()->after('phone');
        });

        DB::table('restaurants')->orderBy('id')->each(function (object $restaurant): void {
            DB::table('restaurants')->where('id', $restaurant->id)->update([
                'email' => sprintf('restaurant-%d@order-service.test', $restaurant->id),
            ]);
        });

        Schema::table('restaurants', function (Blueprint $table): void {
            $table->unique('email');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        DB::table('orders')->orderBy('id')->each(function (object $order): void {
            $email = $order->client_email ?: sprintf('legacy-client-%d@order-service.test', $order->id);

            DB::table('clients')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => $order->client_name,
                    'phone' => $order->client_phone,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            DB::table('orders')->where('id', $order->id)->update([
                'client_id' => DB::table('clients')->where('email', $email)->value('id'),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('client_id');
        });

        Schema::table('restaurants', function (Blueprint $table): void {
            $table->dropUnique(['email']);
            $table->dropColumn('email');
        });

        Schema::dropIfExists('clients');
    }
};
