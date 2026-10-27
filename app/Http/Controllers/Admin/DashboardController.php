<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function dashboard(): View
    {
        return view('admin.dashboard.home');
    }

    public function login(): View
    {
        return view('auth.login');
    }

    public function processToLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            $this->logActivity('login_failed', 'Failed login attempt', null, [
                'email' => $credentials['email'],
            ], $request);

            return back()
                ->withErrors(['email' => 'The provided credentials are invalid or the account is inactive.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $this->logActivity('login', 'User logged in successfully', Auth::user(), [], $request);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $this->logActivity('logout', 'User logged out', $user, [], $request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function logActivity(
        string $event,
        string $description,
        $user = null,
        array $extraProperties = [],
        ?Request $request = null,
    ): void {
        $request ??= request();

        try {
            $logger = activity('authentication')
                ->withProperties(array_merge([
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_agent' => $request->userAgent(),
                ], $extraProperties))
                ->event($event);

            if ($user) {
                $logger->causedBy($user)->performedOn($user);
            }

            $logger->log($description);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
