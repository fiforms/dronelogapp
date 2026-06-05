<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DefaultChecklistItem;
use App\Models\DefaultRiskItem;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

        $googleUser = Socialite::driver('google')->user();

        // Email already registered without Google — hard fail.
        $existing = User::where('email', $googleUser->getEmail())
            ->whereNull('google_id')
            ->first();

        if ($existing) {
            return redirect("{$frontendUrl}/login?error=email_taken");
        }

        $user = User::firstOrCreate(
            ['google_id' => $googleUser->getId()],
            [
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'password'          => null,
                'email_verified_at' => now(),
            ]
        );

        if ($user->wasRecentlyCreated) {
            $team = Team::create(['name' => "{$user->name}'s Team"]);
            $team->users()->attach($user, ['role' => 'owner']);
            DefaultChecklistItem::seedTeam($team);
            DefaultRiskItem::seedTeam($team);
            event(new Registered($user));
        }

        Auth::login($user, remember: true);

        return redirect($frontendUrl);
    }
}
