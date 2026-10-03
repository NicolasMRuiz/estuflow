<?php

namespace App\Livewire;

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
        abort_unless(Auth::check(), 403);

        return view('livewire.dashboard', ['user' => Auth::user()]);
    }
}
