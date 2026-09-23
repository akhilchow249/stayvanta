<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AboutController extends Controller
{
    public function edit(): InertiaResponse
    {
        return Inertia::render('settings/about');
    }
}