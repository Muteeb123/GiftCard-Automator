<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'embedded';

    public function __construct()
    {
        if(!request()->get('shop')) {
            $this->rootView = 'non_embedded';
        }
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
   public function share(Request $request): array
{
    $user = Auth::user();

   

    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $user,
        ],

     
        'ziggy' => function () use ($request) {
            return array_merge((new \Tighten\Ziggy\Ziggy)->toArray(), [
                'location' => $request->url(),
                'query' => $request->query(),
            ]);
        },
    ]);
}

}
