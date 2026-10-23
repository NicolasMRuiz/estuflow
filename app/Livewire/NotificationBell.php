<?php

namespace App\Livewire;

use App\Models\InboxNotice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public function render(): View
    {
        abort_unless(Auth::check() && Auth::user()->hasVerifiedEmail(), 403);

        return view('livewire.notification-bell', ['unread' => InboxNotice::where('user_id', Auth::id())->whereNull('read_at')->count()]);
    }
}
