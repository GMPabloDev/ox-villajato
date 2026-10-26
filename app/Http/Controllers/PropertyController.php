<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('properties/create');
    }
}
