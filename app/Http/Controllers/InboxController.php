<?php

namespace App\Http\Controllers;

use App\Models\BoardPost;
use App\Models\CalendarEvent;
use App\Models\FeedSync;
use App\Models\InboxNotice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(Request $r): View
    {
        $filters = $r->validate(['state' => ['nullable', 'in:unread,read'], 'type' => ['nullable', 'in:reminder,board']]);
        $notices = InboxNotice::where('user_id', $r->user()->id)->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))->when($filters['state'] ?? null, fn ($q, $state) => $state === 'read' ? $q->whereNotNull('read_at') : $q->whereNull('read_at'))->orderByDesc('id')->paginate(15)->withQueryString();

        return view('inbox.index', ['notices' => $notices, 'filters' => $filters]);
    }

    public function read(Request $r, int $notice): RedirectResponse
    {
        $n = InboxNotice::where('user_id', $r->user()->id)->findOrFail($notice);
        if (! $n->read_at) {
            $n->update(['read_at' => now()]);
        }

        return to_route('inbox.index', $r->only('state', 'type'))->with('status', 'Notificación marcada como leída.');
    }

    public function readAll(Request $r): RedirectResponse
    {
        InboxNotice::where('user_id', $r->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return to_route('inbox.index')->with('status', 'Marcaste todas tus notificaciones como leídas.');
    }

    public function open(Request $r, int $notice): View|RedirectResponse
    {
        $n = InboxNotice::where('user_id', $r->user()->id)->findOrFail($notice);
        if ($n->type === 'board') {
            return to_route('board.show', $n->target_id);
        }
        $event = CalendarEvent::where('user_id', $r->user()->id)->find($n->target_id);

        return $event ? to_route('calendar.show', $event->id) : view('inbox.unavailable');
    }

    public function board(): View
    {
        return view('board.index', ['posts' => BoardPost::orderByDesc('published_on')->orderByDesc('id')->paginate(12), 'sync' => FeedSync::find('board')]);
    }

    public function post(int $post): View
    {
        return view('board.show', ['post' => BoardPost::findOrFail($post)]);
    }
}
