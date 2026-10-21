<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SitePageController extends Controller
{
    public function about(): View
    {
        return view('public.pages.about', [
            'title' => 'درباره ما',
        ]);
    }

    public function contact(): View
    {
        return view('public.pages.contact', [
            'title' => 'تماس با ما',
        ]);
    }

    public function privacy(): View
    {
        return view('public.pages.privacy', [
            'title' => 'حریم خصوصی و پیامک',
        ]);
    }
}
