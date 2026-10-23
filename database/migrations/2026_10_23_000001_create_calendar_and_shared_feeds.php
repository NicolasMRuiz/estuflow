<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_course_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('title', 160);
            $t->string('type', 20);
            $t->text('description')->nullable();
            $t->string('place', 200)->nullable();
            $t->boolean('all_day')->default(false);
            $t->timestampTz('starts_at')->nullable();
            $t->timestampTz('ends_at')->nullable();
            $t->date('start_date');
            $t->date('end_date');
            $t->jsonb('reminder_options');
            $t->boolean('email_reminders')->default(false);
            $t->unsignedInteger('schedule_version')->default(1);
            $t->timestampsTz();
            $t->softDeletesTz();
            $t->index(['user_id', 'start_date', 'end_date']);
        });
        Schema::create('event_reminders', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->string('offset', 30);
            $t->timestampTz('due_at')->index();
            $t->timestampTz('useful_until');
            $t->string('internal_status', 20)->default('pending');
            $t->string('email_status', 20)->default('off');
            $t->timestampTz('email_claimed_at')->nullable();
            $t->timestampTz('email_sent_at')->nullable();
            $t->unsignedSmallInteger('email_attempts')->default(0);
            $t->timestampsTz();
            $t->unique(['calendar_event_id', 'version', 'offset']);
        });
        Schema::create('reminder_email_budgets', function (Blueprint $t): void {
            $t->date('day')->primary();
            $t->unsignedInteger('reserved')->default(0);
        });
        Schema::create('personal_notes', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title', 100)->nullable();
            $t->text('content');
            $t->timestampsTz();
            $t->index(['user_id', 'updated_at']);
        });
        Schema::create('feed_syncs', function (Blueprint $t): void {
            $t->string('key')->primary();
            $t->timestampTz('last_attempt_at')->nullable();
            $t->timestampTz('last_success_at')->nullable();
            $t->timestampTz('next_attempt_at')->nullable();
            $t->timestampTz('leased_until')->nullable();
            $t->unsignedInteger('failures')->default(0);
            $t->string('last_error', 80)->nullable();
        });
        Schema::create('holidays', function (Blueprint $t): void {
            $t->id();
            $t->unsignedSmallInteger('year')->index();
            $t->date('date')->index();
            $t->string('type', 100);
            $t->string('name', 250);
            $t->unique(['year', 'date', 'type', 'name']);
        });
        Schema::create('board_posts', function (Blueprint $t): void {
            $t->id();
            $t->string('source_key', 64)->unique();
            $t->string('content_hash', 64);
            $t->string('title', 250);
            $t->text('excerpt');
            $t->date('published_on')->nullable();
            $t->jsonb('attachments');
            $t->timestampTz('detected_at');
            $t->timestampsTz();
        });
        Schema::create('board_recipients', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('board_post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestampTz('delivered_at')->nullable();
            $t->unique(['board_post_id', 'user_id']);
        });
        Schema::create('inbox_notices', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('dedupe_key', 100);
            $t->string('type', 20);
            $t->string('title', 250);
            $t->text('body');
            $t->unsignedBigInteger('target_id');
            $t->timestampTz('read_at')->nullable();
            $t->timestampsTz();
            $t->unique(['user_id', 'dedupe_key']);
            $t->index(['user_id', 'read_at', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['inbox_notices', 'board_recipients', 'board_posts', 'holidays', 'feed_syncs', 'personal_notes', 'reminder_email_budgets', 'event_reminders', 'calendar_events'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
