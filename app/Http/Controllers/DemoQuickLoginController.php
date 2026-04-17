<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoQuickLoginController extends Controller
{
    /**
     * @return array<string, string> role key => seeded email (see DemoUsersSeeder + CourtClientSeeder)
     */
    public static function roleEmails(): array
    {
        return [
            'super_admin' => 'superadmin@picklecorner.ph',
            'player' => 'player@picklecorner.ph',
            'open_play_host' => 'openplayhost@picklecorner.ph',
            'court_admin' => 'courtadmin@picklecorner.ph',
            'desk' => 'desk@picklecorner.ph',
            'coach' => 'coach@picklecorner.ph',
        ];
    }

    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(config('demo.quick_login_enabled'), 404);

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:'.implode(',', array_keys(self::roleEmails()))],
        ]);

        $email = self::roleEmails()[$validated['role']];
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return redirect()->back()->with(
                'warning',
                'Demo accounts were not found. From the project root, run: php artisan migrate --seed',
            );
        }

        Auth::login($user);
        $request->session()->regenerate();

        ActivityLogger::log(
            'auth.demo_quick_login',
            ['role' => $validated['role'], 'email' => $user->email],
            $user,
            'Demo quick login ('.$validated['role'].')',
        );

        $url = $user->usesStaffAppNav()
            ? $user->staffAppHomeUrl()
            : $user->memberHomeUrl();

        return redirect()->intended($url);
    }
}
