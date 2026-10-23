<?php

namespace App\Http\Controllers;

use App\Models\PersonalNote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotesController extends Controller
{
    private function own(Request $r, int $note): PersonalNote
    {
        return PersonalNote::where('user_id', $r->user()->id)->findOrFail($note);
    }

    public function index(Request $r): View
    {
        return view('notes.index', ['notes' => PersonalNote::where('user_id', $r->user()->id)->orderByDesc('updated_at')->orderByDesc('id')->paginate(12)]);
    }

    public function create(): View
    {
        return view('notes.form', ['note' => new PersonalNote]);
    }

    public function show(Request $r, int $note): View
    {
        return view('notes.show', ['note' => $this->own($r, $note)]);
    }

    public function edit(Request $r, int $note): View
    {
        return view('notes.form', ['note' => $this->own($r, $note)]);
    }

    public function store(Request $r): RedirectResponse
    {
        return $this->save($r, new PersonalNote(['user_id' => $r->user()->id]));
    }

    public function update(Request $r, int $note): RedirectResponse
    {
        return $this->save($r, $this->own($r, $note));
    }

    private function save(Request $r, PersonalNote $note): RedirectResponse
    {
        $data = $r->validate(['title' => ['nullable', 'string', 'max:100'], 'content' => ['required', 'string', 'max:5000'], 'user_id' => ['prohibited']]);
        $note->fill($data)->save();

        return to_route('notes.show', $note->id)->with('status', 'Nota guardada.');
    }

    public function confirm(Request $r, int $note): View
    {
        return view('notes.delete', ['note' => $this->own($r, $note)]);
    }

    public function destroy(Request $r, int $note): RedirectResponse
    {
        $r->validate(['confirm' => ['accepted']]);
        $this->own($r, $note)->delete();

        return redirect()->to(route('notes.index').'#nueva-nota')->with('status', 'Nota eliminada.');
    }
}
