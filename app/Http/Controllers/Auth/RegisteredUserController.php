<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): Response
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
        ]);

        // Every new user gets their own personal team automatically
        $team = Team::create(['name' => "{$user->name}'s Team"]);
        $team->users()->attach($user, ['role' => 'owner']);

        // Seed the default pre-flight checklist for this team
        $template = $team->checklistTemplates()->create([
            'name'       => 'Standard Pre-Flight',
            'is_default' => true,
        ]);
        foreach ([
            'Weather Check: Wind speed, precipitation and visibility within limits',
            'Equipment Check: Arms fully extended, propellers in visually good condition, gimbal clear of obstructions, battery secure.',
            'Location Check: Acceptable visibility of flight area, flight path clear of obstructions',
            'Clearance Check: Permitted to fly in airspace (LAANC check, no TFRs), and permitted to launch and control from launch location',
        ] as $i => $label) {
            $template->items()->create(['sort_order' => $i + 1, 'label' => $label]);
        }

        event(new Registered($user));

        Auth::login($user);

        return response()->noContent();
    }
}
