<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Inertia\Inertia;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public function __invoke(Request $request)
    {
        // Load the 'main' menu and its tree structure
        $mainMenu = Menu::where('slug', 'main')->with('tree')->first();

        return Inertia::render('Welcome', [
            'menu' => $mainMenu,
        ]);
    }
}
