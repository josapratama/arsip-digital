<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function index()
    {
        return Inertia::render('Landing', [
            'settings' => SiteSetting::getAllCached(),
        ]);
    }
}
