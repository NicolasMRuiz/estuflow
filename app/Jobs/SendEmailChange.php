<?php

namespace App\Jobs;

use App\Models\PendingEmailChange;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailChange implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 25;

    public function __construct(public string $changeId, public string $purpose, public string $token) {}

    public function failed(?Throwable $exception): void
    {
        if (in_array($this->purpose, ['current', 'proposed'], true)) {
            PendingEmailChange::whereKey($this->changeId)->where($this->purpose.'_delivery', 'queued')->update([$this->purpose.'_delivery' => 'failed']);
        }
    }

    public function handle(): void
    {
        // Lock ordering matches request/cancel/confirm. A superseded queued job is inert.
        DB::transaction(function (): void {
            $userId = PendingEmailChange::whereKey($this->changeId)->value('user_id');
            $user = User::whereKey($userId)->lockForUpdate()->first();
            $change = PendingEmailChange::whereKey($this->changeId)->lockForUpdate()->first();
            if (! $user || ! $change?->accepts($user, $this->purpose, $this->token) || $change->getAttribute($this->purpose.'_delivery') !== 'queued') {
                return;
            }
            $address = $this->purpose === 'current' ? $change->current_email : $change->proposed_email;
            $url = route('profile.email.confirm', ['change' => $change->id, 'purpose' => $this->purpose, 'token' => $this->token]);
            try {
                $sent = Mail::raw("Solicitaste cambiar tu email en EstuFlow. Para autorizar este cambio, abrí el enlace e ingresá a tu cuenta actual:\n".$url."\nEl enlace vence en 60 minutos. Necesitás confirmar ambos correos. Si no lo pediste, cancelalo desde Mi perfil; tu email activo se conserva.", fn ($m) => $m->to($address)->subject('Confirmá el cambio de email · EstuFlow'));
                $state = $sent === null ? 'failed' : 'sent';
            } catch (Throwable) {
                // SMTP may have accepted DATA. Don't log links or retry an ambiguous send.
                $state = 'failed';
            }
            $change->setAttribute($this->purpose.'_delivery', $state);
            $change->save();
        });
    }
}
