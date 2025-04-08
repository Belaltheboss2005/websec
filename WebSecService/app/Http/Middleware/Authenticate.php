<?php
use Illuminate\Http\Request;

class Authenticate
{
    protected function redirectTo(Request $request)
    {
        // // Check if the current route is 'forgetPasswordEmail'
        // if ($request->route()->getName() === 'forgetPasswordEmail') {
        //     return null; // Do not redirect
        // }

        if (!$request->expectsJson()) {
            return route('login'); // Redirect to login if not authenticated
        }
    }
}
