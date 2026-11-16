<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use RuntimeException;
use Throwable;

class GoogleIntegrationController extends Controller
{
    private const CALENDAR_SCOPE =
        'https://www.googleapis.com/auth/calendar.events';

    private const DRIVE_SCOPE =
        'https://www.googleapis.com/auth/drive.file';

    public function connect(): RedirectResponse
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            abort(403);
        }

        if (!$user->hasVerifiedEmail() || !$user->roles()->exists()) {
            abort(403);
        }

        abort_unless(
            config('services.google.client_id')
                && config('services.google.client_secret'),
            503,
            'Google OAuth is not configured.'
        );

        session()->put(
            'google_integration_user_id',
            $user->id
        );

        /** @var AbstractProvider $googleProvider */
        $googleProvider = Socialite::driver('google');

        return $googleProvider
            ->redirectUrl(
                route('integrations.google.callback')
            )
            ->scopes([
                self::CALENDAR_SCOPE,
                self::DRIVE_SCOPE,
            ])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
            ])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            abort(403);
        }

        if (!$user->hasVerifiedEmail() || !$user->roles()->exists()) {
            abort(403);
        }

        $expectedUserId = $request->session()
            ->pull('google_integration_user_id');

        abort_unless(
            $expectedUserId !== null
                && (string) $expectedUserId === (string) $user->id,
            403
        );

        if ($request->filled('error')) {
            return redirect('/admin')->with(
                'error',
                'Google authorization was cancelled.'
            );
        }

        try {
            /** @var AbstractProvider $googleProvider */
            $googleProvider = Socialite::driver('google');

            $googleUser = $googleProvider
                ->redirectUrl(
                    route('integrations.google.callback')
                )
                ->user();

            if (!$googleUser->getId() || !$googleUser->token) {
                throw new RuntimeException(
                    'Invalid Google authorization.'
                );
            }

            $approvedScopes = $googleUser->approvedScopes ?? [];

            if (!is_array($approvedScopes)) {
                throw new RuntimeException(
                    'Invalid Google authorization scopes.'
                );
            }

            if (!in_array(
                self::CALENDAR_SCOPE,
                $approvedScopes,
                true
            )) {
                throw new RuntimeException(
                    'Calendar permission not granted.'
                );
            }

            if (!in_array(
                self::DRIVE_SCOPE,
                $approvedScopes,
                true
            )) {
                throw new RuntimeException(
                    'Google Drive permission not granted.'
                );
            }

            $connection = $user
                ->googleConnection()
                ->firstOrNew();

            $sameAccount = $connection->exists
                && $connection->google_account_id
                    === $googleUser->getId();

            if ($connection->exists && !$sameAccount) {
                throw new RuntimeException(
                    'A different Google account is already connected.'
                );
            }

            $refreshToken = $googleUser->refreshToken
                ?: (
                    $sameAccount
                        ? $connection->refresh_token
                        : null
                );

            if (!$refreshToken) {
                throw new RuntimeException(
                    'Refresh token unavailable.'
                );
            }

            $connection->google_account_id =
                $googleUser->getId();

            $connection->google_email =
                $googleUser->getEmail();

            $connection->access_token =
                $googleUser->token;

            $connection->refresh_token =
                $refreshToken;

            $connection->expires_at =
                $googleUser->expiresIn
                    ? now()->addSeconds(
                        (int) $googleUser->expiresIn
                    )
                    : null;

            $connection->scopes = $approvedScopes;

            $connection->save();

            return redirect('/admin')->with(
                'success',
                'Google Calendar and Drive connected successfully.'
            );

        } catch (Throwable $exception) {
            Log::warning(
                'Google integration authorization failed.',
                [
                    'user_id' => $user->id,
                    'exception_type' => get_class($exception),
                ]
            );

            return redirect('/admin')->with(
                'error',
                'Unable to connect Google Calendar and Drive.'
            );
        }
    }
}