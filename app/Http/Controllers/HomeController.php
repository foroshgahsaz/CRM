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
            ? RequestType::query()->active()->with('activeFields')->orderBy('sort_order')->get()
            : collect();

        $prefill = session('submission.prefill', []);
        if (! is_array($prefill)) {
            $prefill = [];
        }

        return view('public.home', [
            'types' => $types,
            'prefill' => $prefill,
        ]);
    }
}
