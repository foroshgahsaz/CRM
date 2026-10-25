<?php

namespace App\Http\Controllers;

use App\Models\RequestType;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('public.home', [
            'types' => RequestType::query()->active()->orderBy('sort_order')->limit(3)->get(),
        ]);
    }
}
