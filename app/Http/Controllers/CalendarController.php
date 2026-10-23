<?php

namespace App\Http\Controllers;

use App\Academic\AcademicProgress;
use App\Calendar\Events;
use App\Models\CalendarEvent;
use App\Models\FeedSync;
use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __construct(private Events $events) {}

    /** @return array<string, string> */
    public function context(Request $request): array
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m', 'after_or_equal:1900-01', 'before_or_equal:2199-12'], 'view' => ['nullable', 'in:auto,month,agenda'], 'day' => ['nullable', 'date_format:Y-m-d'], 'filter_type' => ['nullable', 'in:exam,assignment,class,other']]);

        return array_filter($data, fn ($v) => is_string($v) && $v !== '') + ['month' => now(config('m04.timezone'))->format('Y-m'), 'view' => 'auto'];
    }

    public function index(Request $request): View
    {
        $context = $this->context($request);
        $month = CarbonImmutable::createFromFormat('!Y-m', $context['month'], config('m04.timezone'));
        $from = $month->startOfWeek();
        $until = $month->endOfMonth()->endOfWeek();
        $query = CalendarEvent::where('user_id', $request->user()->id)->whereDate('start_date', '<=', $until->toDateString())->whereDate('end_date', '>=', $from->toDateString())->when($context['filter_type'] ?? null, fn ($q, $type) => $q->where('type', $type))->with('course')->orderBy('start_date')->orderBy('starts_at')->orderBy('id');
        $events = $query->get();
        $holidays = Holiday::whereBetween('date', [$from->toDateString(), $until->toDateString()])->orderBy('date')->get();
        $days = [];
        for ($day = $from; $day->lte($until); $day = $day->addDay()) {
            $date = $day->toDateString();
            $days[] = ['date' => $day, 'events' => $events->filter(fn ($event) => $event->start_date->toDateString() <= $date && $event->end_date->toDateString() >= $date), 'holidays' => $holidays->filter(fn ($h) => $h->date->toDateString() === $date)];
        }

        return view('calendar.index', ['context' => $context, 'month' => $month, 'days' => $days, 'agenda' => (clone $query)->when(! isset($context['day']), fn ($q) => $q->whereDate('start_date', '<=', $month->endOfMonth()->toDateString())->whereDate('end_date', '>=', $month->toDateString()))->when($context['day'] ?? null, fn ($q, $date) => $q->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date))->paginate(20)->withQueryString(), 'holidays' => $holidays, 'syncs' => FeedSync::whereIn('key', ['holidays:'.$month->year, 'holidays:'.($month->year + 1)])->get()->keyBy('key')]);
    }

    public function create(Request $request): View
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        return $this->form($request, new CalendarEvent(['start_date' => $request->query('date', now(config('m04.timezone'))->toDateString()), 'type' => 'exam', 'all_day' => false, 'reminder_options' => []]));
    }

    private function form(Request $request, CalendarEvent $event): View
    {
        $enrollment = app(AcademicProgress::class)->active($request->user());
        $courses = $enrollment->plan->courses;
        if ($event->plan_course_id && ! $courses->contains('id', $event->plan_course_id)) {
            $courses->push($event->course);
        }

        return view('calendar.form', ['event' => $event, 'courses' => $courses, 'context' => $this->context($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $context = $this->context($request);
        $event = $this->events->save($request->user(), $request->all());

        return to_route('calendar.show', ['event' => $event->id] + $context)->with('status', 'Evento guardado. Los avisos vencidos se omitieron.');
    }

    public function show(Request $request, int $event): View
    {
        return view('calendar.show', ['event' => $this->events->own($request->user(), $event)->load('course'), 'context' => $this->context($request)]);
    }

    public function edit(Request $request, int $event): View
    {
        return $this->form($request, $this->events->own($request->user(), $event));
    }

    public function update(Request $request, int $event): RedirectResponse
    {
        $context = $this->context($request);
        $this->events->save($request->user(), $request->all(), $event);

        return to_route('calendar.show', ['event' => $event] + $context)->with('status', 'Cambios guardados. Actualizamos los avisos pendientes si cambió la programación.');
    }

    public function confirm(Request $request, int $event): View
    {
        return view('calendar.delete', ['event' => $this->events->own($request->user(), $event), 'context' => $this->context($request)]);
    }

    public function destroy(Request $request, int $event): RedirectResponse
    {
        $context = $this->context($request);
        $request->validate(['confirm' => ['accepted']]);
        $this->events->delete($request->user(), $event);

        return redirect()->to(route('calendar.index', $context).'#nuevo-evento')->with('status', 'Evento eliminado. Cancelamos sus avisos pendientes.');
    }
}
