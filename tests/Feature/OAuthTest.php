<?php

namespace Tests\Feature;

use App\Auth\OAuthAccounts;
use App\Auth\OAuthProfile;
use App\Auth\OAuthProviders;
use App\Models\OAuthIdentity;
use App\Models\User;
use App\Notifications\VerifyEmail;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialUser;
use Mockery;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    use RefreshDatabase;

    private const TENANT = '9188040d-6c67-4c5b-b112-36a304b66dad';

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        foreach (['google', 'microsoft'] as $provider) {
            config([
                "services.$provider.enabled" => true,
                "services.$provider.client_id" => "$provider-client",
                "services.$provider.client_secret" => 'fictitious-local-test-secret',
                "services.$provider.redirect" => rtrim(config('app.url'), '/')."/auth/$provider/callback",
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function attempt(string $provider = 'google', string $intent = 'login', ?User $user = null): array
    {
        return [
            'state' => 'valid-state',
            'oauth.nonce' => 'valid-nonce',
            'oauth.attempt' => ['provider' => $provider, 'intent' => $intent, 'user_id' => $user?->id, 'expires_at' => now()->addMinutes(10)->timestamp],
        ];
    }

    /** @param array<string, mixed> $claims */
    private function mockProvider(string $provider = 'google', ?string $email = 'oauth@example.test', string $subject = 'stable-subject', array $claims = []): void
    {
        $driver = Mockery::mock($provider === 'google' ? GoogleProvider::class : MicrosoftProvider::class);
        $driver->shouldReceive('user')->once()->andReturn((new SocialUser)->map([
            'id' => $provider === 'microsoft' ? 'graph-object-not-identity' : $subject,
            'name' => 'Persona OAuth', 'email' => $email,
        ]));
        if ($provider === 'microsoft') {
            $driver->shouldReceive('getClaims')->once()->andReturn((object) array_replace([
                'tid' => self::TENANT,
                'iss' => 'https://login.microsoftonline.com/'.self::TENANT.'/v2.0',
                'sub' => $subject, 'aud' => 'microsoft-client', 'nonce' => 'valid-nonce',
            ], $claims));
        }
        $providers = Mockery::mock(OAuthProviders::class)->makePartial();
        $providers->shouldReceive('driver')->with($provider)->once()->andReturn($driver);
        $this->app->instance(OAuthProviders::class, $providers);
    }

    private function identity(User $user, string $provider = 'google', string $subject = 'stable-subject'): OAuthIdentity
    {
        return OAuthIdentity::query()->create([
            'user_id' => $user->id, 'provider' => $provider, 'subject' => $subject,
            'issuer' => $provider === 'google' ? 'https://accounts.google.com' : 'https://login.microsoftonline.com/'.self::TENANT.'/v2.0',
        ]);
    }

    public function test_buttons_only_appear_when_enabled_and_exactly_configured(): void
    {
        $this->get('/login')->assertSee('Continuar con Google')->assertSee('Iniciar sesión con Microsoft');
        config(['services.google.enabled' => false, 'services.microsoft.client_secret' => null]);
        $this->get('/login')->assertDontSee('Continuar con Google')->assertDontSee('Iniciar sesión con Microsoft');
        config(['services.google.enabled' => true, 'services.google.redirect' => 'https://foreign.example/callback']);
        $this->get('/registro')->assertDontSee('Continuar con Google');
    }

    public function test_google_authorization_uses_exact_callback_state_pkce_and_identity_scopes(): void
    {
        $response = $this->get('/auth/google/redirect')->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame(config('services.google.redirect'), $query['redirect_uri']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertNotEmpty($query['code_challenge']);
        $this->assertSame(session('state'), $query['state']);
        $this->assertSame('openid profile email', $query['scope']);
        $this->assertNotEmpty(session('code_verifier'));
    }

    public function test_microsoft_authorization_uses_common_pkce_nonce_and_no_extra_permissions(): void
    {
        $response = $this->get('/auth/microsoft/redirect')->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://login.microsoftonline.com/common/oauth2/v2.0/authorize?', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(session('oauth.nonce'), $query['nonce']);
        $this->assertSame('openid profile email User.Read', $query['scope']);
        $this->assertSame(config('services.microsoft.redirect'), $query['redirect_uri']);
    }

    public function test_unknown_and_disabled_providers_are_rejected(): void
    {
        $this->get('/auth/unknown/redirect')->assertNotFound();
        config(['services.google.enabled' => false]);
        $this->get('/auth/google/redirect')->assertNotFound();
        $this->get('/auth/google/callback')->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_wrong_state_never_creates_or_authenticates_a_user(): void
    {
        $this->withSession($this->attempt())->get('/auth/google/callback?state=forged&code=fictitious')
            ->assertRedirect(route('login'))->assertSessionHasErrors('oauth')->assertSessionMissing('oauth.attempt')->assertSessionMissing('state');
        $this->assertGuest();
        $this->assertDatabaseCount('oauth_identities', 0);
    }

    public function test_socialite_itself_rejects_invalid_state_before_any_network_call(): void
    {
        $request = Request::create('/auth/google/callback?state=wrong&code=fictitious');
        $request->setLaravelSession(session()->driver());
        session()->put('state', 'valid-state');
        $handler = new MockHandler([]);
        $driver = (new GoogleProvider($request, 'client', 'secret', 'http://localhost/auth/google/callback'))
            ->setHttpClient(new Client(['handler' => HandlerStack::create($handler)]))->enablePKCE();
        $this->expectException(InvalidStateException::class);
        $driver->user();
    }

    public function test_cancelled_authorization_has_an_actionable_message(): void
    {
        $this->withSession($this->attempt('microsoft'))->get('/auth/microsoft/callback?state=valid-state&error=access_denied&error_description=untrusted')
            ->assertRedirect(route('login'))->assertSessionHasErrors('oauth')
            ->assertSessionHas('errors', fn ($errors) => str_contains($errors->first('oauth'), 'institución'));
        $this->assertGuest();
    }

    public function test_new_google_account_has_no_password_and_requires_estuflow_email_verification(): void
    {
        $this->mockProvider();
        $this->withSession($this->attempt())->get('/auth/google/callback?state=valid-state&code=fictitious')
            ->assertRedirect(route('verification.notice'));
        $user = User::sole();
        $this->assertNull($user->password);
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertDatabaseHas('oauth_identities', ['user_id' => $user->id, 'provider' => 'google', 'subject' => 'stable-subject']);
        $this->assertSame(['id', 'user_id', 'provider', 'issuer', 'subject', 'created_at', 'updated_at'], array_keys(OAuthIdentity::sole()->getAttributes()));
        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_microsoft_persists_validated_issuer_and_subject_instead_of_graph_id_or_email(): void
    {
        $this->mockProvider('microsoft');
        $this->withSession($this->attempt('microsoft'))->get('/auth/microsoft/callback?state=valid-state&code=fictitious')
            ->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('oauth_identities', ['provider' => 'microsoft', 'subject' => 'stable-subject', 'issuer' => 'https://login.microsoftonline.com/'.self::TENANT.'/v2.0']);
        $this->assertDatabaseMissing('oauth_identities', ['subject' => 'graph-object-not-identity']);
    }

    public function test_microsoft_wrong_nonce_is_rejected(): void
    {
        $this->mockProvider('microsoft', claims: ['nonce' => 'other-attempt']);
        $this->withSession($this->attempt('microsoft'))->get('/auth/microsoft/callback?state=valid-state&code=fictitious')
            ->assertSessionHasErrors('oauth');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_microsoft_audience_must_match_exactly_not_as_a_substring(): void
    {
        $this->mockProvider('microsoft', claims: ['aud' => 'prefix-microsoft-client-suffix']);
        $this->withSession($this->attempt('microsoft'))->get('/auth/microsoft/callback?state=valid-state&code=fictitious')
            ->assertSessionHasErrors('oauth');
        $this->assertGuest();
    }

    public function test_microsoft_issuer_must_match_validated_tenant(): void
    {
        $this->mockProvider('microsoft', claims: ['iss' => 'https://foreign.example/issuer']);
        $this->withSession($this->attempt('microsoft'))->get('/auth/microsoft/callback?state=valid-state&code=fictitious')
            ->assertSessionHasErrors('oauth');
        $this->assertGuest();
    }

    public function test_provider_exception_payloads_are_never_flashed_even_for_domain_exceptions(): void
    {
        $driver = Mockery::mock(GoogleProvider::class);
        $driver->shouldReceive('user')->once()->andThrow(new \DomainException('fictitious-private-provider-token'));
        $providers = Mockery::mock(OAuthProviders::class)->makePartial();
        $providers->shouldReceive('driver')->with('google')->once()->andReturn($driver);
        $this->app->instance(OAuthProviders::class, $providers);
        $this->withSession($this->attempt())->get('/auth/google/callback?state=valid-state&code=fictitious')
            ->assertRedirect(route('login'))->assertSessionHasErrors('oauth')
            ->assertSessionHas('errors', fn ($errors) => ! str_contains($errors->first('oauth'), 'fictitious-private-provider-token'));
        $this->assertGuest();
    }

    public function test_microsoft_same_subject_in_a_different_tenant_cannot_enter_another_tenants_account(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.test']);
        $this->identity($owner, 'microsoft');
        $otherTenant = '12345678-1234-1234-1234-123456789abc';
        $this->mockProvider('microsoft', claims: ['tid' => $otherTenant, 'iss' => "https://login.microsoftonline.com/$otherTenant/v2.0"]);
        $this->withSession($this->attempt('microsoft'))->get('/auth/microsoft/callback?state=valid-state&code=fictitious')
            ->assertRedirect(route('verification.notice'));
        $this->assertNotEquals($owner->id, auth()->id());
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('oauth_identities', 2);
    }

    public function test_existing_identity_logs_into_its_owner_without_overwriting_local_email(): void
    {
        $owner = User::factory()->create(['email' => 'local@example.test']);
        $this->identity($owner);
        $this->mockProvider(email: 'changed-provider@example.test');
        $this->withSession($this->attempt())->get('/auth/google/callback?state=valid-state&code=fictitious')->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($owner);
        $this->assertSame('local@example.test', $owner->fresh()->email);
        $this->get('/dashboard')->assertRedirect(route('academic.choose'));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_existing_unverified_identity_goes_to_verification_notice(): void
    {
        $owner = User::factory()->unverified()->create();
        $this->identity($owner);
        $this->mockProvider();
        $this->withSession($this->attempt())->get('/auth/google/callback?state=valid-state&code=fictitious')->assertRedirect(route('verification.notice'));
        $this->assertAuthenticatedAs($owner);
    }

    public function test_matching_local_email_is_not_automatically_linked(): void
    {
        User::factory()->create(['email' => 'oauth@example.test']);
        $this->mockProvider();
        $this->withSession($this->attempt())->get('/auth/google/callback?state=valid-state&code=fictitious')
            ->assertRedirect(route('login'))->assertSessionHasErrors('oauth');
        $this->assertDatabaseCount('oauth_identities', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_missing_email_is_collected_without_accepting_browser_identity_fields(): void
    {
        $this->mockProvider(email: null);
        $this->withSession($this->attempt())->get('/auth/google/callback?state=valid-state&code=fictitious')
            ->assertRedirect(route('oauth.email'))->assertSessionHas('oauth.pending');
        $this->get('/auth/completar-email')->assertOk()->assertSee('¿A qué email te escribimos?')->assertDontSee('stable-subject');
        $this->post('/auth/completar-email', ['email' => '  PERSONA@example.test ', 'subject' => 'forged-id', 'provider' => 'microsoft', 'user_id' => 123, 'email_verified_at' => now()])
            ->assertRedirect(route('verification.notice'))->assertSessionMissing('oauth.pending');
        $this->assertDatabaseHas('users', ['email' => 'persona@example.test', 'email_verified_at' => null, 'password' => null]);
        $this->assertDatabaseHas('oauth_identities', ['provider' => 'google', 'subject' => 'stable-subject']);
        $this->assertDatabaseMissing('oauth_identities', ['subject' => 'forged-id']);
    }

    public function test_missing_email_step_rejects_invalid_input_and_expired_server_identity(): void
    {
        $this->withSession(['oauth.pending' => ['provider' => 'google', 'issuer' => 'https://accounts.google.com', 'subject' => 'stable', 'name' => 'Persona', 'expires_at' => now()->addMinutes(10)->timestamp]]);
        $this->post('/auth/completar-email', ['email' => '<script>'])->assertSessionHasErrors('email');
        $this->travel(11)->minutes();
        $this->post('/auth/completar-email', ['email' => 'persona@example.test'])->assertRedirect(route('login'))->assertSessionMissing('oauth.pending');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_callback_context_expires_and_cannot_be_replayed(): void
    {
        $this->withSession($this->attempt());
        $this->travel(11)->minutes();
        $this->get('/auth/google/callback?state=valid-state&code=fictitious')->assertSessionHasErrors('oauth');
        $this->get('/auth/google/callback?state=valid-state&code=fictitious')->assertSessionHasErrors('oauth');
        $this->assertGuest();
    }

    public function test_link_requires_verified_account_and_recent_reauthentication(): void
    {
        $this->post('/metodos-de-acceso/google/vincular')->assertRedirect(route('login'));
        $unverified = User::factory()->unverified()->create();
        $this->actingAs($unverified)->post('/metodos-de-acceso/google/vincular')->assertRedirect(route('verification.notice'));
        $verified = User::factory()->create();
        $this->actingAs($verified)->post('/metodos-de-acceso/google/vincular')
            ->assertRedirect(route('access-methods.index'))->assertSessionHasErrors('oauth');
        $this->assertDatabaseCount('oauth_identities', 0);
    }

    public function test_password_confirmation_is_bound_to_current_user_and_allows_link(): void
    {
        $user = User::factory()->create(['password' => 'test-password-for-link']);
        $this->actingAs($user)->post('/metodos-de-acceso/reautenticar', ['password' => 'incorrect'])
            ->assertSessionHasErrors('auth')->assertSessionMissing('_old_input.password');
        $this->post('/metodos-de-acceso/reautenticar', ['password' => 'test-password-for-link'])
            ->assertRedirect(route('access-methods.index'))->assertSessionHas('oauth.reauthenticated_user_id', $user->id);
        $this->post('/metodos-de-acceso/google/vincular')->assertRedirect();
        $this->mockProvider();
        $this->withSession($this->attempt('google', 'link', $user))->get('/auth/google/callback?state=valid-state&code=fictitious')
            ->assertRedirect(route('access-methods.index'))->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('oauth_identities', ['user_id' => $user->id, 'provider' => 'google']);
    }

    public function test_link_never_transfers_identity_owned_by_another_account(): void
    {
        $owner = User::factory()->create();
        $current = User::factory()->create();
        $this->identity($owner);
        $this->mockProvider();
        $this->actingAs($current)->withSession([
            ...$this->attempt('google', 'link', $current),
            'auth.password_confirmed_at' => now()->timestamp, 'oauth.reauthenticated_user_id' => $current->id,
        ])->get('/auth/google/callback?state=valid-state&code=fictitious')->assertSessionHasErrors('oauth');
        $this->assertAuthenticatedAs($current);
        $this->assertDatabaseHas('oauth_identities', ['user_id' => $owner->id, 'subject' => 'stable-subject']);
    }

    public function test_link_callback_is_bound_to_actor_even_if_session_account_changes(): void
    {
        $initiator = User::factory()->create();
        $current = User::factory()->create();
        $this->mockProvider();
        $this->actingAs($current)->withSession([
            ...$this->attempt('google', 'link', $initiator),
            'auth.password_confirmed_at' => now()->timestamp, 'oauth.reauthenticated_user_id' => $current->id,
        ])->get('/auth/google/callback?state=valid-state&code=fictitious')->assertSessionHasErrors('oauth');
        $this->assertAuthenticatedAs($current);
        $this->assertDatabaseCount('oauth_identities', 0);
    }

    public function test_oauth_reauthentication_requires_a_previously_linked_identity(): void
    {
        $user = User::factory()->create(['password' => null]);
        $this->identity($user);
        $this->mockProvider();
        $this->actingAs($user)->withSession($this->attempt('google', 'reauthenticate', $user))
            ->get('/auth/google/callback?state=valid-state&code=fictitious')
            ->assertRedirect(route('access-methods.index'))->assertSessionHas('oauth.reauthenticated_user_id', $user->id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_oauth_reauthentication_rejects_another_identity_without_switching_account(): void
    {
        $user = User::factory()->create(['password' => null]);
        $other = User::factory()->create();
        $this->identity($user, subject: 'own-identity');
        $this->identity($other);
        $this->mockProvider();
        $this->actingAs($user)->withSession($this->attempt('google', 'reauthenticate', $user))
            ->get('/auth/google/callback?state=valid-state&code=fictitious')
            ->assertSessionHasErrors('oauth')->assertSessionMissing('oauth.reauthenticated_user_id');
        $this->assertAuthenticatedAs($user);
    }

    public function test_duplicate_identity_creation_is_idempotent_with_unique_database_constraints(): void
    {
        $profile = new OAuthProfile('google', 'https://accounts.google.com', 'same-sub', 'Persona', 'persona@example.test');
        $accounts = app(OAuthAccounts::class);
        $first = $accounts->login($profile);
        $second = $accounts->login($profile);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('oauth_identities', 1);
        $accounts->link($first, $profile);
        $this->assertDatabaseCount('oauth_identities', 1);
    }

    public function test_database_refuses_the_same_external_identity_on_two_users(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->identity($first);
        $this->expectException(UniqueConstraintViolationException::class);
        $this->identity($second);
    }

    public function test_reauthentication_expires_before_a_link_callback(): void
    {
        $user = User::factory()->create();
        $this->mockProvider();
        $this->actingAs($user)->withSession([
            ...$this->attempt('google', 'link', $user),
            'auth.password_confirmed_at' => now()->subMinutes(11)->timestamp,
            'oauth.reauthenticated_user_id' => $user->id,
        ])->get('/auth/google/callback?state=valid-state&code=fictitious')->assertSessionHasErrors('oauth');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('oauth_identities', 0);
    }

    public function test_existing_identity_with_missing_provider_email_still_logs_into_its_owner(): void
    {
        $owner = User::factory()->create();
        $this->identity($owner);
        $this->mockProvider(email: null);
        $this->withSession($this->attempt())->get('/auth/google/callback?state=valid-state&code=fictitious')->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($owner);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_signed_internal_intended_url_can_resume_after_oauth_login(): void
    {
        $user = User::factory()->unverified()->create();
        $this->identity($user);
        $this->mockProvider();
        $intended = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->withSession([...$this->attempt(), 'url.intended' => $intended])->get('/auth/google/callback?state=valid-state&code=fictitious')->assertRedirect($intended);
    }

    public function test_oauth_only_account_cannot_login_with_a_local_password(): void
    {
        User::factory()->create(['email' => 'oauth@example.test', 'password' => null]);
        $this->post('/login', ['email' => 'oauth@example.test', 'password' => 'any-fictitious-password'])->assertSessionHasErrors('auth');
        $this->assertGuest();
    }

    public function test_access_methods_show_linked_methods_and_password_setup_path_for_oauth_account(): void
    {
        $user = User::factory()->create(['password' => null]);
        $this->identity($user);
        $this->actingAs($user)->get('/metodos-de-acceso')->assertRedirect(route('profile.index'));
        $this->get('/mi-perfil')->assertOk()->assertSee('Métodos de acceso')->assertSee('Establecer contraseña')->assertSee('Vinculado a tu cuenta.');
        $this->post('/logout', ['recover' => '1'])->assertRedirect(route('password.request'));
        $this->assertGuest();
        $this->assertDatabaseHas('oauth_identities', ['user_id' => $user->id, 'provider' => 'google']);
    }

    public function test_external_intended_url_is_rejected_after_oauth_login(): void
    {
        $user = User::factory()->create();
        $this->identity($user);
        $this->mockProvider();
        $this->withSession([...$this->attempt(), 'url.intended' => 'https://foreign.example/phishing'])
            ->get('/auth/google/callback?state=valid-state&code=fictitious')->assertRedirect(route('dashboard'));
    }

    public function test_hostile_referer_cannot_redirect_password_confirmation_errors_off_site(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withHeader('Referer', 'https://foreign.example/phishing')
            ->post('/metodos-de-acceso/reautenticar', ['password' => ''])
            ->assertRedirect(route('access-methods.index'))->assertSessionHasErrors('password');
        $this->post('/metodos-de-acceso/reautenticar', ['password' => 'wrong'])
            ->assertRedirect(route('access-methods.index'))->assertSessionHasErrors('auth');
    }

    public function test_hostile_referer_cannot_redirect_pending_email_conflicts_off_site(): void
    {
        User::factory()->create(['email' => 'occupied@example.test']);
        $this->withSession(['oauth.pending' => ['provider' => 'google', 'issuer' => 'https://accounts.google.com', 'subject' => 'stable', 'name' => 'Persona', 'expires_at' => now()->addMinutes(10)->timestamp]])
            ->withHeader('Referer', 'https://foreign.example/phishing')
            ->post('/auth/completar-email', ['email' => 'occupied@example.test'])
            ->assertRedirect(route('oauth.email'))->assertSessionHasErrors('oauth');
        $this->assertDatabaseCount('oauth_identities', 0);
    }

    public function test_profile_provider_reauthentication_returns_to_initiated_operation_and_confirms_only_linked_account(): void
    {
        $user = User::factory()->create(['password' => null]);
        $this->identity($user);
        $this->mockProvider();
        $this->actingAs($user)->get('/mi-perfil/contrasena')->assertOk();
        $this->withSession([...$this->attempt('google', 'reauthenticate', $user), 'oauth.attempt' => [...$this->attempt('google', 'reauthenticate', $user)['oauth.attempt'], 'return_to' => 'profile.password'], 'profile.operation' => 'email'])
            ->get('/auth/google/callback?state=valid-state&code=fictitious')
            ->assertRedirect(route('profile.password'))->assertSessionHas('profile.provider_confirmation.user_id', $user->id);
        $this->post('/mi-perfil/contrasena', ['password' => 'new-fictitious-profile-password', 'password_confirmation' => 'new-fictitious-profile-password'])->assertRedirect(route('profile.index'));
        $this->assertDatabaseHas('oauth_identities', ['user_id' => $user->id, 'subject' => 'stable-subject']);
    }

    public function test_profile_wrong_provider_identity_cannot_establish_local_password(): void
    {
        $user = User::factory()->create(['password' => null]);
        $other = User::factory()->create();
        $this->identity($user, subject: 'own-identity');
        $this->identity($other);
        $this->mockProvider();
        $this->actingAs($user)->withSession([...$this->attempt('google', 'reauthenticate', $user), 'oauth.attempt' => [...$this->attempt('google', 'reauthenticate', $user)['oauth.attempt'], 'return_to' => 'profile.password']])
            ->get('/auth/google/callback?state=valid-state&code=fictitious')->assertRedirect(route('profile.password'))->assertSessionHasErrors('oauth')->assertSessionMissing('profile.provider_confirmation');
        $this->post('/mi-perfil/contrasena', ['password' => 'new-fictitious-profile-password', 'password_confirmation' => 'new-fictitious-profile-password'])->assertSessionHasErrors('account');
        $this->assertNull($user->fresh()->password);
        $this->assertAuthenticatedAs($user);
    }

    public function test_oauth_known_identity_never_overwrites_edited_profile_even_with_different_email(): void
    {
        $user = User::factory()->create(['name' => 'Nombre propio editado', 'email' => 'changed@example.test']);
        $this->identity($user);
        User::factory()->create(['email' => 'oauth@example.test']);
        $this->mockProvider();
        $this->withSession($this->attempt())->get('/auth/google/callback?state=valid-state&code=fictitious')->assertRedirect(route('dashboard'));
        $this->assertSame('changed@example.test', $user->fresh()->email);
        $this->assertSame('Nombre propio editado', $user->fresh()->name);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('oauth_identities', 1);
    }

    public function test_profile_password_reauthentication_returns_to_email_and_does_not_store_external_destination(): void
    {
        $user = User::factory()->create(['password' => 'current-fictitious-password']);
        $this->actingAs($user)->get('/mi-perfil/email');
        $this->post('/metodos-de-acceso/reautenticar', ['password' => 'current-fictitious-password', 'return_to' => 'https://foreign.example'])->assertRedirect(route('profile.email'));
        $this->assertAuthenticatedAs($user);
    }
}
