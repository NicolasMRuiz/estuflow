<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->jsonb('metadata');
        });
        Schema::create('academic_careers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->restrictOnDelete();
            $table->string('key');
            $table->string('name');
            $table->unique(['institution_id', 'key']);
        });
        Schema::create('study_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_career_id')->constrained()->restrictOnDelete();
            $table->string('key')->unique();
            $table->string('resolution')->nullable();
            $table->unsignedSmallInteger('entry_cohort_from');
            $table->unsignedSmallInteger('entry_cohort_to')->nullable();
            $table->jsonb('source_data');
            $table->char('content_hash', 64);
        });
        Schema::create('plan_courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('study_plan_id')->constrained()->restrictOnDelete();
            $table->string('key')->unique();
            $table->string('code');
            $table->unsignedSmallInteger('academic_year');
            $table->unsignedSmallInteger('catalog_order');
            $table->string('source_name');
            $table->string('display_name');
            $table->char('field_type', 1);
            $table->char('accreditation_instance', 1);
            $table->unsignedSmallInteger('hours');
            $table->boolean('free_exam_allowed');
            $table->jsonb('source_data');
            $table->unique(['study_plan_id', 'code']);
            $table->unique(['study_plan_id', 'catalog_order']);
            $table->unique(['id', 'study_plan_id']);
        });
        Schema::create('course_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('study_plan_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('plan_course_id');
            $table->unsignedBigInteger('required_course_id');
            $table->string('kind');
            $table->foreign(['plan_course_id', 'study_plan_id'])->references(['id', 'study_plan_id'])->on('plan_courses')->restrictOnDelete();
            $table->foreign(['required_course_id', 'study_plan_id'])->references(['id', 'study_plan_id'])->on('plan_courses')->restrictOnDelete();
            $table->unique(['plan_course_id', 'required_course_id', 'kind']);
        });
        Schema::create('academic_year_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('study_plan_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('academic_year');
            $table->string('kind');
            $table->jsonb('source_data');
            $table->unique(['study_plan_id', 'academic_year']);
        });
        Schema::create('course_special_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_course_id')->constrained()->restrictOnDelete();
            $table->string('kind');
            $table->jsonb('source_data');
            $table->unique(['plan_course_id', 'kind']);
        });
        Schema::create('academic_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('study_plan_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'study_plan_id']);
            $table->unique(['id', 'study_plan_id']);
        });
        Schema::create('course_progress', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('academic_enrollment_id');
            $table->unsignedBigInteger('study_plan_id');
            $table->unsignedBigInteger('plan_course_id');
            $table->string('status')->default('pending');
            $table->string('approval_method')->nullable();
            $table->timestamps();
            $table->foreign(['academic_enrollment_id', 'study_plan_id'])->references(['id', 'study_plan_id'])->on('academic_enrollments')->restrictOnDelete();
            $table->foreign(['plan_course_id', 'study_plan_id'])->references(['id', 'study_plan_id'])->on('plan_courses')->restrictOnDelete();
            $table->unique(['academic_enrollment_id', 'plan_course_id']);
        });
        DB::statement('CREATE UNIQUE INDEX one_active_plan_per_user ON academic_enrollments (user_id) WHERE is_active');
        DB::statement("ALTER TABLE plan_courses ADD CONSTRAINT valid_course CHECK (academic_year BETWEEN 1 AND 3 AND hours > 0 AND field_type IN ('G','E') AND accreditation_instance IN ('R','P'))");
        DB::statement('ALTER TABLE course_requirements ADD CONSTRAINT no_self_requirement CHECK (plan_course_id <> required_course_id)');
        DB::statement("ALTER TABLE course_requirements ADD CONSTRAINT valid_requirement_kind CHECK (kind IN ('to_take_final_requires_finals', 'to_enroll_requires_coursework', 'to_enroll_requires_finals'))");
        DB::statement("ALTER TABLE course_progress ADD CONSTRAINT valid_progress CHECK ((status IN ('pending','in_progress','regularized') AND approval_method IS NULL) OR (status = 'approved' AND approval_method IN ('final','promotion','unspecified')))");
    }

    public function down(): void
    {
        foreach (['course_progress', 'academic_enrollments', 'course_special_rules', 'academic_year_rules', 'course_requirements', 'plan_courses', 'study_plans', 'academic_careers', 'institutions'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
