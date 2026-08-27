<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rider_assignments', function (Blueprint $table): void {
            $table->ulid('assignment_id')->nullable()->unique()->after('id');
        });

        DB::table('rider_assignments')->orderBy('id')->each(function (object $assignment): void {
            DB::table('rider_assignments')->where('id', $assignment->id)->update([
                'assignment_id' => (string) Str::ulid(),
                'status' => $assignment->status === 'active' ? 'confirmed' : $assignment->status,
            ]);
        });

        DB::statement("CREATE UNIQUE INDEX rider_assignments_active_rider_unique ON rider_assignments (rider_id) WHERE status IN ('pending', 'confirmed')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX rider_assignments_active_rider_unique');

        Schema::table('rider_assignments', function (Blueprint $table): void {
            $table->dropUnique(['assignment_id']);
            $table->dropColumn('assignment_id');
        });
    }
};
