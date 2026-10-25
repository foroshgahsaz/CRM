<?php

namespace App\Http\Controllers;

use App\Models\RequestType;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $types = Schema::hasTable('request_types')
            ? RequestType::query()->active()->orderBy('sort_order')->limit(3)->get()
            : collect();

        return view('public.home', [
            'types' => $types,
        ]);
    }
}
