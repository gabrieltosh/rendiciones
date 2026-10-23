<?php

namespace App\Http\Middleware;

use App\Models\Accountability;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'errors' => fn() => session()->get('errors') ? session()->get('errors')->getBag('default')->getMessages() : (object) [],
            'title' => fn() => session()->get('title') ? session()->get('title') : 'App',
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'status' => fn () => $request->session()->get('status'),
                'type' => fn () => $request->session()->get('type'),
            ],
            'auth.user' => fn () => $request->user()
                ? $request->user()->only(
                    'name',
                    'email',
                    'username',
                    'type',
                    'distribution_rule_one',
                    'distribution_rule_second',
                    'distribution_rule_three',
                    'distribution_rule_four',
                    'distribution_rule_five',
                    'card_code'
                )
                : null,
            // Contadores para los badges del menú lateral
            'menu_counts' => fn () => $this->menuCounts($request),
        ]);
    }

    private function menuCounts(Request $request): ?array
    {
        $user = $request->user();
        if (!$user || !in_array($user->type, ['Administrador', 'Autorizador'])) {
            return null;
        }

        return [
            'authorization' => Accountability::pendingAuthorizationFor($user->id)->count(),
            'pending_sap' => Accountability::pendingSapExport()->count(),
        ];
    }
}
