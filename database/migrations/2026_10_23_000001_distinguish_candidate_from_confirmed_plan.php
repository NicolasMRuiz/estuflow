<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_enrollments', function (Blueprint $table): void {
            $table->boolean('is_candidate')->default(false);
        });
        DB::transaction(function (): void {
            // Recover a previously confirmed plan if the original M03 switch
            // left its replacement active but unfinished. Keep every progress row.
            $drafts = DB::table('academic_enrollments')->where('is_active', true)->whereNull('completed_at')->get();
            foreach ($drafts as $draft) {
                DB::table('academic_enrollments')->where('id', $draft->id)->update(['is_candidate' => true]);
                $confirmed = DB::table('academic_enrollments')->where('user_id', $draft->user_id)->whereNotNull('completed_at')->orderByDesc('completed_at')->orderByDesc('id')->first();
                if ($confirmed) {
                    DB::table('academic_enrollments')->where('id', $draft->id)->update(['is_active' => false]);
                    DB::table('academic_enrollments')->where('id', $confirmed->id)->update(['is_active' => true]);
                }
            }
        });
        DB::statement('CREATE UNIQUE INDEX one_candidate_plan_per_user ON academic_enrollments (user_id) WHERE is_candidate');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX one_candidate_plan_per_user');
        Schema::table('academic_enrollments', fn (Blueprint $table) => $table->dropColumn('is_candidate'));
    }
};
