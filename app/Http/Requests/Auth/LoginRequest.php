<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class LoginRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void {
        $this->ensureIsNotRateLimited();

        $email = $this->email;
        $password = $this->password;
        $user = null;

        // Προσπαθούμε να βρούμε χρήστη με το δοθέν email
        $user = \App\Models\User::where('email', $email)->first();

        // Αν δε βρήκαμε χρήστη, ψάχνουμε αν το email ανήκει σε κάποιο tenant
        if (!$user) {
            $tenant = \App\Models\Tenant::where('email', $email)->first();
            if ($tenant && $tenant->owner_id) {
                // Βρίσκουμε τον owner του tenant
                $user = \App\Models\User::find($tenant->owner_id);
            }
        }

        // Αν δε βρήκαμε χρήστη με κανέναν τρόπο, επιστρέφουμε σφάλμα
        if (!$user) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Ελέγχουμε το password
        if (!Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Ελέγχουμε αν ο χρήστης ανήκει σε tenant και αν αυτός είναι εγκεκριμένος
        if ($user->tenant_id) {
            $tenant = $user->tenant;
            if (!$tenant || !$tenant->is_active) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => 'Ο λογαριασμός σας δεν έχει εγκριθεί ακόμα. Παρακαλώ περιμένετε την έγκριση από τον διαχειριστή.',
                ]);
            }
        }

        // Εάν όλα είναι εντάξει, συνδέουμε τον χρήστη
        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}
