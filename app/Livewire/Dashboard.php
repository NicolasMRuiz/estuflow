<?php

namespace App\Livewire;

use App\Academic\AcademicProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render(): View
    {
        // No public property or URL parameter can choose the displayed account.
        abort_unless(Auth::check() && Auth::user()->hasVerifiedEmail(), 403);

        $progress = app(AcademicProgress::class);
        $enrollment = $progress->active(Auth::user());
        abort_unless($enrollment?->completed_at !== null, 403);

        $rows = $enrollment->progress->keyBy('plan_course_id');
        $currentCourses = $enrollment->plan->courses->filter(fn ($course) => $rows[$course->id]->status === 'in_progress');

        $suggestedCourses = $enrollment->plan->courses->filter(fn ($course) => $rows[$course->id]->status === 'pending')->take(3);

        return view('livewire.dashboard', ['user' => Auth::user(), 'enrollment' => $enrollment, 'counts' => $progress->summary($enrollment), 'currentCourses' => $currentCourses->take(3), 'suggestedCourses' => $suggestedCourses]);
    }
}
