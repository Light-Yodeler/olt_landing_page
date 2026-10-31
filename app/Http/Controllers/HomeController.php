<?php

namespace App\Http\Controllers;

use App\Services\LandingPageService;
use App\Services\VisitorTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class HomeController extends Controller
{
    public function __construct(
        protected LandingPageService $landingPageService,
        protected VisitorTrackingService $visitorTrackingService
    ) {}

    public function index(Request $request): View
    {
        try {
            $this->visitorTrackingService->recordVisit($request);
        } catch (Throwable $e) {
            Log::warning('Visitor tracking skipped in HomeController: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return view('home', $this->landingPageService->getLandingPageData());
    }

    public function switchLocale(string $locale): RedirectResponse
    {
        return $this->landingPageService->switchLocale($locale);
    }
}
