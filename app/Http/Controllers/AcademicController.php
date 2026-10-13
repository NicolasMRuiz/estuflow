<?php

namespace App\Http\Controllers;

use App\Academic\AcademicProgress;
use App\Academic\Advisory;
use App\Models\StudyPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicController extends Controller
{
    public function __construct(private AcademicProgress $progress) {}

    public function choose(Request $request): View
    {
        return view('academic.choose', ['plans' => StudyPlan::with('career')->orderBy('id')->get(), 'enrollment' => $this->progress->active($request->user())]);
    }

    public function select(Request $request): RedirectResponse
    {
        $current = $this->progress->active($request->user());
        $data = $request->validate(['plan_id' => ['required', 'integer', 'exists:study_plans,id'], 'confirm_change' => $current ? ['required', 'accepted'] : ['sometimes', 'accepted'], 'user_id' => ['prohibited'], 'enrollment_id' => ['prohibited']]);
        $this->progress->select($request->user(), (int) $data['plan_id']);

        return to_route('academic.draft');
    }

    public function draft(Request $request): View|RedirectResponse
    {
        $enrollment = $this->progress->active($request->user());
        if (! $enrollment) {
            return to_route('academic.choose');
        }

        return view('academic.draft', ['enrollment' => $enrollment, 'rows' => $enrollment->progress->keyBy('plan_course_id')]);
    }

    public function saveDraft(Request $request): RedirectResponse
    {
        $data = $request->validate(['entries' => ['required', 'array'], 'user_id' => ['prohibited'], 'enrollment_id' => ['prohibited']]);
        $this->progress->save($request->user(), $data['entries'], draft: true);

        return $request->input('next') === 'summary' ? to_route('academic.summary') : to_route('academic.draft')->with('status', 'Borrador guardado. Podés retomarlo cuando quieras.');
    }

    public function summary(Request $request): View|RedirectResponse
    {
        $enrollment = $this->progress->active($request->user());
        if (! $enrollment) {
            return to_route('academic.choose');
        }

        return view('academic.summary', ['enrollment' => $enrollment, 'counts' => $this->progress->summary($enrollment), 'rows' => $enrollment->progress->keyBy('plan_course_id')]);
    }

    public function finish(Request $request): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted'], 'user_id' => ['prohibited'], 'enrollment_id' => ['prohibited']]);
        $this->progress->finish($request->user());

        return to_route('dashboard')->with('status', 'Tu carrera quedó configurada.');
    }

    public function career(Request $request): View
    {
        $data = $request->validate(['year' => ['nullable', 'integer', 'between:1,3'], 'status' => ['nullable', Rule::in(array_keys(AcademicProgress::STATUSES))]]);
        $enrollment = $this->progress->active($request->user());
        $rows = $enrollment->progress->keyBy('plan_course_id');
        $courses = $enrollment->plan->courses->filter(fn ($course) => (! ($data['year'] ?? null) || $course->academic_year === (int) $data['year']) && (! ($data['status'] ?? null) || $rows[$course->id]->status === $data['status']));

        return view('academic.career', ['enrollment' => $enrollment, 'rows' => $rows, 'courses' => $courses, 'counts' => $this->progress->summary($enrollment)]);
    }

    public function detail(Request $request, int $course): View
    {
        $selected = $this->progress->ownCourse($request->user(), $course);
        $enrollment = $this->progress->active($request->user());
        $advisory = new Advisory($enrollment);

        return view('academic.detail', ['course' => $selected, 'enrollment' => $enrollment, 'guidance' => $advisory->course($selected)]);
    }

    public function update(Request $request, int $course): RedirectResponse
    {
        $this->progress->ownCourse($request->user(), $course);
        $request->validate(['user_id' => ['prohibited'], 'enrollment_id' => ['prohibited'], 'course_id' => ['prohibited']]);
        $this->progress->save($request->user(), [$course => $request->only('status', 'approval_method')]);

        return to_route('career.detail', $course)->with('status', 'Estado guardado.');
    }
}
