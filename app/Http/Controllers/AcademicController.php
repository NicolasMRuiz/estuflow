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
        return view('academic.choose', ['plans' => StudyPlan::with('career')->withCount('courses')->orderBy('id')->get(), 'enrollment' => $this->progress->active($request->user()), 'candidate' => $this->progress->candidate($request->user())]);
    }

    public function select(Request $request): RedirectResponse
    {
        $data = $request->validate(['plan_id' => ['required', 'integer', 'exists:study_plans,id'], 'user_id' => ['prohibited'], 'enrollment_id' => ['prohibited']], ['plan_id.required' => 'Elegí una carrera para continuar.', 'plan_id.integer' => 'Elegí una carrera disponible para continuar.', 'plan_id.exists' => 'Elegí una carrera disponible para continuar.']);
        $selected = $this->progress->select($request->user(), (int) $data['plan_id']);
        $request->session()->forget('academic.review');

        return to_route($selected->is_candidate ? 'academic.draft' : 'career.index');
    }

    public function draft(Request $request): View|RedirectResponse
    {
        $enrollment = $this->progress->candidate($request->user());
        if (! $enrollment) {
            return to_route('academic.choose');
        }

        return view('academic.draft', ['enrollment' => $enrollment, 'rows' => $enrollment->progress->keyBy('plan_course_id'), 'changing' => $this->progress->active($request->user())?->completed_at !== null]);
    }

    public function saveDraft(Request $request): RedirectResponse
    {
        $data = $request->validate(['entries' => ['required', 'array'], 'user_id' => ['prohibited'], 'enrollment_id' => ['prohibited']]);
        $this->progress->save($request->user(), $data['entries'], draft: true);
        $request->session()->forget('academic.review');

        return $request->input('next') === 'summary' ? to_route('academic.summary') : to_route('academic.draft')->with('status', 'Borrador guardado. Podés retomarlo cuando quieras.');
    }

    public function summary(Request $request): View|RedirectResponse
    {
        $enrollment = $this->progress->candidate($request->user());
        if (! $enrollment) {
            return to_route('academic.choose');
        }
        $request->session()->put('academic.review', $this->progress->fingerprint($enrollment));

        return view('academic.summary', ['enrollment' => $enrollment, 'counts' => $this->progress->summary($enrollment), 'rows' => $enrollment->progress->keyBy('plan_course_id'), 'changing' => $this->progress->active($request->user())?->completed_at !== null]);
    }

    public function finish(Request $request): RedirectResponse
    {
        $data = $request->validate(['candidate_id' => ['required', 'integer'], 'user_id' => ['prohibited'], 'enrollment_id' => ['prohibited']], ['candidate_id.required' => 'Volvé a revisar el resumen antes de confirmar.', 'candidate_id.integer' => 'Volvé a revisar el resumen antes de confirmar.']);
        $this->progress->finish($request->user(), (int) $data['candidate_id'], $request->session()->get('academic.review'));
        $request->session()->forget('academic.review');

        return to_route('dashboard')->with('status', 'Tu carrera quedó configurada.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->progress->cancel($request->user());
        $request->session()->forget('academic.review');

        return to_route($this->progress->active($request->user())?->completed_at ? 'career.index' : 'academic.choose')->with('status', 'Cancelaste el cambio. Tu carrera se conserva.');
    }

    public function career(Request $request): View
    {
        $data = $request->validate(['year' => ['nullable', 'integer', 'between:1,3'], 'status' => ['nullable', Rule::in(array_keys(AcademicProgress::STATUSES))]]);

        return view('academic.career', ['year' => (string) ($data['year'] ?? ''), 'statusFilter' => $data['status'] ?? '']);
    }

    public function detail(Request $request, int $course): View
    {
        $selected = $this->progress->ownCourse($request->user(), $course);
        $enrollment = $this->progress->active($request->user());
        $advisory = new Advisory($enrollment);

        return view('academic.detail', ['course' => $selected, 'enrollment' => $enrollment, 'guidance' => $advisory->course($selected), 'context' => $this->context($request)]);
    }

    public function update(Request $request, int $course): RedirectResponse
    {
        $this->progress->ownCourse($request->user(), $course);
        $request->validate(['user_id' => ['prohibited'], 'enrollment_id' => ['prohibited'], 'course_id' => ['prohibited']]);
        $context = $this->context($request);
        $this->progress->save($request->user(), [$course => $request->only('status', 'approval_method')]);
        if (($context['return_to'] ?? null) === 'career') {
            $removed = ($context['filter_status'] ?? '') !== '' && $context['filter_status'] !== $request->input('status');

            return redirect()->to(route('career.index', ['year' => $context['year'] ?? '', 'status' => $context['filter_status'] ?? '']).($removed ? '#resultados' : '#materia-'.$course))->with('status', $removed ? 'Guardamos los cambios. La materia ya no coincide con el filtro elegido.' : 'Guardamos los cambios.');
        }

        return to_route('career.detail', $course)->with('status', 'Guardamos los cambios.');
    }

    /** @return array<string, mixed> */
    private function context(Request $request): array
    {
        return $request->validate(['return_to' => ['nullable', 'in:career'], 'year' => ['nullable', 'integer', 'between:1,3'], 'filter_status' => ['nullable', Rule::in(array_keys(AcademicProgress::STATUSES))]]);
    }
}
