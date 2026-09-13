<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SsoApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class SsoController extends Controller
{
    public function redirect(Request $request)
    {
        try {
            $config = $this->configuration();
        } catch (RuntimeException) {
            return $this->failure();
        }

        $state = bin2hex(random_bytes(32));
        $request->session()->put('sso', ['state' => $state, 'expires' => time() + 600]);

        return redirect()->away($config['base_url'].'/oauth/authorize?'.http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => '',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(Request $request)
    {
        $pending = $request->session()->pull('sso');
        $state = $request->query('state');
        $code = $request->query('code');

        if (! is_array($pending) || ! is_string($state) || ! is_string($pending['state'] ?? null)
            || ! hash_equals($pending['state'], $state) || ($pending['expires'] ?? 0) < time()
            || $request->query('error') !== null || ! is_string($code) || $code === '' || strlen($code) > 4096) {
            return $this->failure();
        }

        try {
            $config = $this->configuration();
            $http = Http::acceptJson()->connectTimeout(5)->timeout(15)
                ->withOptions(['verify' => true, 'allow_redirects' => false]);
            $response = (clone $http)->asForm()->post($config['base_url'].'/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'redirect_uri' => $config['redirect_uri'],
                'code' => $code,
            ]);
            $token = $response->json('access_token');
            if (! $response->successful() || ! is_string($token) || $token === ''
                || preg_match('/[\x00-\x20\x7f]/', $token)
                || ! is_string($response->json('token_type'))
                || strcasecmp($response->json('token_type'), 'Bearer') !== 0) {
                return $this->failure();
            }

            $response = $http->withToken($token)->get($config['base_url'].'/api/user');
            $identity = $response->json();
            if (! $response->successful() || ! is_array($identity)) {
                return $this->failure();
            }
            $validator = Validator::make($identity, [
                'id' => ['required', function ($attribute, $value, $fail) {
                    if ((! is_string($value) && ! is_int($value)) || strlen((string) $value) > 255) {
                        $fail('Identitas SSO tidak valid.');
                    }
                }],
                'nik' => ['required', 'string', 'max:255', 'regex:/\A[^\s\p{C}]+\z/u'],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255'],
            ]);
            if ($validator->fails()) {
                return $this->failure();
            }

            $user = User::where('nik', $identity['nik'])->first();
            if (! $user) {
                SsoApplication::firstOrCreate(['nik' => $identity['nik']], [
                    'name' => $identity['name'],
                    'email' => $identity['email'],
                ]);

                return redirect()->route('login')->with('success', 'Pengajuan akses diterima. Menunggu admin melengkapi data dan menyetujui akun.');
            }

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        } catch (Throwable) {
            return $this->failure();
        }
    }

    private function configuration(): array
    {
        $config = config('services.sso');
        foreach (['base_url', 'redirect_uri'] as $key) {
            $url = $config[$key] ?? '';
            $parts = is_string($url) ? parse_url($url) : false;
            if (! $parts || ! filter_var($url, FILTER_VALIDATE_URL) || isset($parts['user']) || isset($parts['pass'])
                || isset($parts['query']) || isset($parts['fragment'])
                || (($parts['scheme'] ?? '') !== 'https'
                    && ! (app()->environment('local', 'testing') && ($parts['scheme'] ?? '') === 'http'
                        && in_array($parts['host'] ?? '', ['localhost', '127.0.0.1', '[::1]'], true)))) {
                throw new RuntimeException('Konfigurasi SSO tidak valid.');
            }
        }
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            throw new RuntimeException('Konfigurasi SSO belum lengkap.');
        }
        $config['base_url'] = rtrim($config['base_url'], '/');

        return $config;
    }

    private function failure()
    {
        return redirect()->route('login')->with('error', 'Login SSO gagal atau kedaluwarsa. Silakan coba lagi atau gunakan password.');
    }
}
