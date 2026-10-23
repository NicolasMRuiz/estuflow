<?php

namespace App\Livewire;

use App\Academic\AcademicProgress;
use App\Models\CalendarEvent;
use App\Models\PersonalNote;
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

        return view('livewire.dashboard', ['upcoming' => CalendarEvent::where('user_id', Auth::id())->where('end_date', '>=', now(config('m04.timezone'))->toDateString())->where(fn ($q) => $q->where('all_day', true)->orWhere('starts_at', '>=', now())->orWhere('ends_at', '>', now()))->orderBy('start_date')->orderBy('starts_at')->limit(3)->get(), 'notes' => PersonalNote::where('user_id', Auth::id())->orderByDesc('updated_at')->orderByDesc('id')->limit(3)->get(), 'user' => Auth::user(), 'enrollment' => $enrollment, 'counts' => $progress->summary($enrollment), 'currentCourses' => $currentCourses->take(3), 'suggestedCourses' => $suggestedCourses]);
    }
}
