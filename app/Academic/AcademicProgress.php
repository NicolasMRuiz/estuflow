<?php

namespace App\Academic;

use App\Models\AcademicEnrollment;
use App\Models\CourseProgress;
use App\Models\PlanCourse;
use App\Models\StudyPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AcademicProgress
{
    public const STATUSES = ['pending' => 'Pendiente', 'in_progress' => 'Cursando', 'regularized' => 'Regularizada', 'approved' => 'Aprobada'];

    public const METHODS = ['unspecified' => 'Sin especificar', 'final' => 'Examen final', 'promotion' => 'Promoción'];

    public function active(User $user): ?AcademicEnrollment
    {
        return AcademicEnrollment::where('user_id', $user->id)->where('is_active', true)->with(['plan.career', 'plan.courses' => fn ($query) => $query->orderBy('catalog_order'), 'plan.courses.requirements.requiredCourse', 'plan.courses.specialRules', 'plan.yearRules', 'progress'])->first();
    }

    public function select(User $user, int $planId): AcademicEnrollment
    {
        return DB::transaction(function () use ($user, $planId): AcademicEnrollment {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $plan = StudyPlan::findOrFail($planId);
            $current = AcademicEnrollment::where('user_id', $user->id)->where('is_active', true)->first();
            if ($current?->study_plan_id === $plan->id) {
                return $current;
            }
            AcademicEnrollment::where('user_id', $user->id)->update(['is_active' => false]);
            $enrollment = AcademicEnrollment::firstOrCreate(['user_id' => $user->id, 'study_plan_id' => $plan->id], ['is_active' => false]);
            $enrollment->update(['is_active' => true, 'completed_at' => null]);
            foreach ($plan->courses as $course) {
                CourseProgress::firstOrCreate(['academic_enrollment_id' => $enrollment->id, 'plan_course_id' => $course->id], ['study_plan_id' => $plan->id, 'status' => 'pending']);
            }

            return $enrollment;
        });
    }

    /** @param array<string|int, mixed> $entries */
    public function save(User $user, array $entries, bool $draft = false): void
    {
        DB::transaction(function () use ($user, $entries, $draft): void {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $enrollment = $this->active($user);
            abort_unless($enrollment !== null, 403);
            $courses = $enrollment->plan->courses->keyBy('id');
            $validated = [];
            foreach ($entries as $id => $entry) {
                $course = $courses->get($id);
                abort_unless($course !== null, 404);
                $prefix = $draft ? 'entries.'.$id : 'entry';
                $payload = $draft ? ['entries' => [$id => $entry]] : ['entry' => $entry];
                $validatedPayload = Validator::make($payload, [
                    $prefix => ['required', 'array:status,approval_method'],
                    $prefix.'.status' => ['required', Rule::in(array_keys(self::STATUSES))],
                    $prefix.'.approval_method' => ['nullable', Rule::in($course->accreditation_instance === 'P' ? array_keys(self::METHODS) : ['unspecified', 'final'])],
                ])->validate();
                $row = $draft ? $validatedPayload['entries'][$id] : $validatedPayload['entry'];
                $validated[$id] = ['status' => $row['status'], 'approval_method' => $row['status'] === 'approved' ? ($row['approval_method'] ?? 'unspecified') : null];
            }
            foreach ($validated as $id => $row) {
                $enrollment->progress()->where('plan_course_id', $id)->firstOrFail()->update($row);
            }
        });
    }

    public function finish(User $user): void
    {
        DB::transaction(function () use ($user): void {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $enrollment = $this->active($user);
            abort_unless($enrollment !== null, 403);
            if ($enrollment->progress->count() !== $enrollment->plan->courses->count()) {
                throw ValidationException::withMessages(['academic' => 'Revisá el borrador antes de confirmar.']);
            }
            $enrollment->update(['completed_at' => now()]);
        });
    }

    /** @return array<string, int> */
    public function summary(AcademicEnrollment $enrollment): array
    {
        $counts = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($enrollment->progress as $progress) {
            $counts[$progress->status]++;
        }
        $counts['total'] = $enrollment->plan->courses->count();
        $counts['percentage'] = $counts['total'] ? (int) round($counts['approved'] * 100 / $counts['total'], 0, PHP_ROUND_HALF_UP) : 0;

        return $counts;
    }

    public function ownCourse(User $user, int $courseId): PlanCourse
    {
        $enrollment = $this->active($user);
        abort_unless($enrollment?->completed_at !== null, 403);

        return $enrollment->plan->courses->firstWhere('id', $courseId) ?? abort(404);
    }
}
