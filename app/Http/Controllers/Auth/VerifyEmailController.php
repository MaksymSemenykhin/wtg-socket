<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Email verification is disabled; redirect to dashboard.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->intended(route('dashboard', absolute: false));
    }
}
