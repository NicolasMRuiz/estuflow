<?php

namespace App\Livewire;

use App\Academic\AcademicProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CourseEditor extends Component
{
    #[Locked]
    public int $courseId;

    public string $status = 'pending';

    public string $approvalMethod = 'unspecified';

    /** @var array<string, mixed> */
    #[Locked]
    public array $context = [];

    /** @param array<string, mixed> $context */
    public function mount(int $courseId, AcademicProgress $progress, array $context = []): void
    {
        $this->courseId = $courseId;
        $this->context = $context;
        $this->authorizeCourse($progress);
        $row = $progress->active(Auth::user())->progress->firstWhere('plan_course_id', $courseId);
        $this->status = $row->status;
        $this->approvalMethod = $row->approval_method ?? 'unspecified';
    }

    public function save(AcademicProgress $progress): void
    {
        $this->authorizeCourse($progress);
        $progress->save(Auth::user(), [$this->courseId => ['status' => $this->status, 'approval_method' => $this->approvalMethod]]);
        if ($this->status !== 'approved') {
            $this->approvalMethod = 'unspecified';
        }
        if (($this->context['return_to'] ?? null) === 'career') {
            $removed = ($this->context['filter_status'] ?? '') !== '' && $this->context['filter_status'] !== $this->status;
            session()->flash('status', $removed ? 'Guardamos los cambios. La materia ya no coincide con el filtro elegido.' : 'Guardamos los cambios.');
            $this->redirect(route('career.index', ['year' => $this->context['year'] ?? '', 'status' => $this->context['filter_status'] ?? '']).($removed ? '#resultados' : '#materia-'.$this->courseId));

            return;
        }
        session()->flash('status', 'Guardamos los cambios.');
        $this->redirectRoute('career.detail', ['course' => $this->courseId]);
    }

    public function render(): View
    {
        $progress = app(AcademicProgress::class);
        $this->authorizeCourse($progress);

        return view('livewire.course-editor', ['course' => $progress->ownCourse(Auth::user(), $this->courseId)]);
    }

    private function authorizeCourse(AcademicProgress $progress): void
    {
        abort_unless(Auth::check() && Auth::user()->hasVerifiedEmail(), 403);
        $progress->ownCourse(Auth::user(), $this->courseId);
    }
}
