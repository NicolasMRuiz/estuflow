<?php

namespace App\Livewire;

use App\Academic\AcademicProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CareerBrowser extends Component
{
    #[Locked]
    public string $year = '';

    #[Locked]
    public string $statusFilter = '';

    #[Locked]
    public ?int $editingCourseId = null;

    public string $status = 'pending';

    public string $approvalMethod = 'unspecified';

    public string $notice = '';

    public function edit(int $courseId, AcademicProgress $progress): void
    {
        $this->authorizeAccount();
        $progress->ownCourse(Auth::user(), $courseId);
        $row = $progress->active(Auth::user())->progress->firstWhere('plan_course_id', $courseId);
        $this->editingCourseId = $courseId;
        $this->status = $row->status;
        $this->approvalMethod = $row->approval_method ?? 'unspecified';
        $this->notice = '';
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->authorizeAccount();
        if ($this->editingCourseId !== null) {
            $this->dispatch('career-focus', courseId: $this->editingCourseId);
        }
        $this->editingCourseId = null;
        $this->resetValidation();
    }

    public function save(AcademicProgress $progress): void
    {
        $this->authorizeAccount();
        abort_unless($this->editingCourseId !== null, 403);
        $progress->ownCourse(Auth::user(), $this->editingCourseId);
        $progress->save(Auth::user(), [$this->editingCourseId => ['status' => $this->status, 'approval_method' => $this->approvalMethod]]);
        $this->notice = $this->statusFilter !== '' && $this->statusFilter !== $this->status ? 'Guardamos los cambios. La materia ya no coincide con el filtro elegido.' : 'Guardamos los cambios.';
        $this->dispatch('career-focus', courseId: $this->editingCourseId);
        $this->editingCourseId = null;
    }

    public function render(): View
    {
        $this->authorizeAccount();
        $progress = app(AcademicProgress::class);
        $enrollment = $progress->active(Auth::user());
        abort_unless($enrollment?->completed_at !== null, 403);
        $rows = $enrollment->progress->keyBy('plan_course_id');
        $courses = $enrollment->plan->courses->filter(fn ($course) => ($this->year === '' || $course->academic_year === (int) $this->year) && ($this->statusFilter === '' || $rows[$course->id]->status === $this->statusFilter));

        return view('livewire.career-browser', ['enrollment' => $enrollment, 'rows' => $rows, 'courses' => $courses, 'counts' => $progress->summary($enrollment), 'candidate' => $progress->candidate(Auth::user())]);
    }

    private function authorizeAccount(): void
    {
        abort_unless(Auth::check() && Auth::user()->hasVerifiedEmail(), 403);
    }
}
