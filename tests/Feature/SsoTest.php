<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\SsoApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['inertia.ssr.enabled' => false]);
        config(['services.sso' => [
            'base_url' => 'http://localhost/sekali_login/public',
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'redirect_uri' => 'http://localhost/sisamsul/public/auth/sso/callback',
        ]]);
        Http::preventStrayRequests();
    }

    private function user(string $nik = '00123'): User
    {
        return User::create([
            'name' => 'Pengguna Lama', 'username' => $nik, 'nik' => $nik,
            'email' => $nik.'@example.test', 'whatsapp' => '6281234567890', 'password' => 'password-lama',
        ]);
    }

    private function identity(array $overrides = []): array
    {
        return array_replace(['id' => 7, 'nik' => '00123', 'name' => 'Nama SSO', 'email' => 'sso@example.test'], $overrides);
    }

    private function fakeIdentity(array $identity): void
    {
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'test-token', 'token_type' => 'Bearer']),
            '*/api/user' => Http::response($identity),
        ]);
    }

    private function ssoCallback(array $query = [])
    {
        return $this->withSession(['sso' => ['state' => 'test-state', 'expires' => time() + 600]])
            ->get('/auth/sso/callback?'.http_build_query(array_replace(['state' => 'test-state', 'code' => 'test-code'], $query)));
    }

    public function test_redirect_uses_random_state_and_standard_authorization_code(): void
    {
        $response = $this->get('/auth/sso');
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame(config('services.sso.redirect_uri'), $query['redirect_uri']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $query['state']);
        $response->assertSessionHas('sso.state', $query['state']);
        $next = $this->get('/auth/sso');
        $this->assertNotSame($response->headers->get('Location'), $next->headers->get('Location'));
        Http::assertNothingSent();
    }

    public function test_existing_user_keeps_password_profile_and_roles(): void
    {
        $user = $this->user();
        $role = Role::create(['name' => 'Operator', 'guard_name' => 'web']);
        $user->assignRole($role);
        $password = $user->password;
        $this->fakeIdentity($this->identity());
        $this->withSession(['existing' => 'value']);
        $oldSession = session()->getId();
        $this->ssoCallback()->assertRedirect(route('dashboard'))->assertSessionMissing('sso');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame('Pengguna Lama', $user->fresh()->name);
        $this->assertSame(['Operator'], $user->fresh()->getRoleNames()->all());
        $this->assertDatabaseCount('sso_applications', 0);
        $this->assertFalse(session()->has('access_token'));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/oauth/token')
            && $request->method() === 'POST' && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
            && $request['grant_type'] === 'authorization_code' && $request['code'] === 'test-code');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/user')
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_unknown_nik_only_creates_one_pending_application_even_with_existing_email(): void
    {
        $user = $this->user('999');
        $this->fakeIdentity($this->identity(['email' => $user->email]));
        $this->ssoCallback()->assertRedirect(route('login'))->assertSessionHas('success');
        $this->ssoCallback()->assertSessionHas('success');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('sso_applications', 1);
        $this->assertDatabaseHas('sso_applications', ['nik' => '00123']);
    }

    public function test_invalid_expired_denied_and_replayed_callbacks_do_not_exchange_tokens(): void
    {
        Http::fake();
        foreach ([['state' => 'wrong'], ['state' => ['bad']], ['code' => ['bad']], ['code' => ''], ['error' => 'access_denied']] as $query) {
            $this->ssoCallback($query)->assertSessionHas('error')->assertSessionMissing('sso');
        }
        $this->withSession(['sso' => ['state' => 'test-state', 'expires' => time() - 1]])
            ->get('/auth/sso/callback?state=test-state&code=test-code')->assertSessionHas('error');
        $this->get('/auth/sso/callback?state=test-state&code=test-code')->assertSessionHas('error');
        Http::assertNothingSent();
        $this->assertGuest();
    }

    public function test_successful_pending_callback_cannot_be_replayed(): void
    {
        $this->fakeIdentity($this->identity());
        $this->ssoCallback()->assertSessionHas('success');
        $this->get('/auth/sso/callback?state=test-state&code=test-code')->assertSessionHas('error');
        Http::assertSentCount(2);
    }

    public function test_malformed_identity_and_http_failures_are_safe(): void
    {
        foreach ([['nik' => 123], ['nik' => ' '], ['nik' => ['123']], ['name' => ''], ['email' => 'invalid'], ['id' => []]] as $override) {
            $this->fakeIdentity($this->identity($override));
            $this->ssoCallback()->assertSessionHas('error');
            $this->assertGuest();
        }
        Http::fake(['*' => Http::response(['error' => 'secret-provider-detail'], 500)]);
        $this->ssoCallback()->assertSessionHas('error', fn ($message) => ! str_contains($message, 'secret-provider-detail'));
        Http::fake(['*' => Http::failedConnection()]);
        $this->ssoCallback()->assertSessionHas('error');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('sso_applications', 0);
    }

    public function test_http_is_rejected_outside_local_environment_or_loopback(): void
    {
        config(['services.sso.base_url' => 'http://example.test']);
        $this->get('/auth/sso')->assertRedirect(route('login'))->assertSessionHas('error');
        config(['services.sso.base_url' => 'http://localhost/sekali_login/public']);
        $this->app->instance('env', 'production');
        $this->get('/auth/sso')->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_password_login_remains_and_public_registration_is_disabled(): void
    {
        $user = $this->user();
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
        $this->post('/login', ['username' => $user->username, 'password' => 'password-lama'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_approval_requires_admin_not_quality_control(): void
    {
        $user = $this->user();
        $user->assignRole(Role::create(['name' => 'Quality Control', 'guard_name' => 'web']));
        $application = SsoApplication::create($this->identityWithoutId('555'));
        $this->actingAs($user)->get('/master/sso-applications')->assertForbidden();
        $this->post('/master/sso-applications/'.$application->id.'/approve', [])->assertForbidden();
        $this->get('/master/users')->assertOk();
    }

    private function identityWithoutId(string $nik): array
    {
        return ['nik' => $nik, 'name' => 'Pengguna Baru', 'email' => 'baru@example.test'];
    }

    public function test_admin_completes_required_data_and_approves_once(): void
    {
        $admin = $this->user();
        $admin->assignRole(Role::create(['name' => 'admin', 'guard_name' => 'web']));
        Role::create(['name' => 'Operator', 'guard_name' => 'web']);
        $department = Departemen::create(['nama' => 'Produksi']);
        $application = SsoApplication::create($this->identityWithoutId('00555'));
        $url = '/master/sso-applications/'.$application->id.'/approve';
        $this->actingAs($admin)->get('/master/sso-applications')->assertOk();
        $this->post($url, [])->assertSessionHasErrors(['nik', 'name', 'email', 'whatsapp', 'departemen_id', 'roles']);
        $data = [...$application->only('nik', 'name', 'email'), 'whatsapp' => '6281234567891', 'departemen_id' => $department->id, 'roles' => ['Operator']];
        $this->post($url, array_replace($data, ['nik' => $admin->nik, 'email' => $admin->email, 'roles' => ['unknown']]))
            ->assertSessionHasErrors(['nik', 'email', 'roles.0']);
        $this->assertDatabaseCount('users', 1);
        $this->post($url, $data)->assertRedirect(route('sso-applications.index'));
        $created = User::where('nik', '00555')->firstOrFail();
        $this->assertSame('00555', $created->username);
        $this->assertTrue($created->hasRole('Operator'));
        $this->assertSame($department->id, $created->departemen_id);
        $this->assertDatabaseCount('sso_applications', 0);
        $this->post($url, $data)->assertNotFound();
        $this->assertDatabaseCount('users', 2);
    }

    public function test_nik_migration_rejects_duplicates_before_schema_changes_and_preserves_accounts(): void
    {
        $user = $this->user();
        $migration = require database_path('migrations/2026_09_13_000001_add_nik_to_users.php');
        $migration->down();
        $duplicate = User::create(['username' => '00123', 'name' => 'Duplikat', 'email' => 'duplicate@example.test', 'whatsapp' => '6281234567892', 'password' => 'password-lain']);
        try {
            $migration->up();
            $this->fail('Duplikat harus ditolak.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('duplikat', $exception->getMessage());
        }
        $this->assertFalse(Schema::hasColumn('users', 'nik'));
        $this->assertDatabaseCount('users', 2);
        DB::table('users')->where('id', $duplicate->id)->update(['username' => '00456']);
        $migration->up();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'nik' => '00123', 'username' => '00123']);
        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));
        $this->assertDatabaseHas('users', ['id' => $duplicate->id, 'nik' => '00456']);
    }
}
