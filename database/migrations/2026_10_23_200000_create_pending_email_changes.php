<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_email_changes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('current_email', 254);
            $table->string('proposed_email', 254);
            foreach (['current', 'proposed'] as $purpose) {
                $table->string($purpose.'_token_hash', 64)->nullable();
                $table->timestampTz($purpose.'_confirmed_at')->nullable();
                $table->string($purpose.'_delivery', 12)->default('queued');
            }
            $table->timestampTz('expires_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_email_changes');
    }
};
