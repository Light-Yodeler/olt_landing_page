<?php

namespace App\Http\Controllers;

use App\Services\LandingPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected LandingPageService $landingPageService
    ) {}

    public function index(): View
    {
        return view('home', $this->landingPageService->getLandingPageData());
    }

    public function switchLocale(string $locale): RedirectResponse
    {
        return $this->landingPageService->switchLocale($locale);
    }
}
