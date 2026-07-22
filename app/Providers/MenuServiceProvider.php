<?php

namespace App\Providers;

use App\Services\DriverService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('layouts.sections.menu.verticalMenu', function ($view) {
            $verticalMenuData = json_decode(
                file_get_contents(base_path('resources/menu/verticalMenu.json'))
            );

            $pendingApprovals = 0;
            try {
                $pendingApprovals = app(DriverService::class)->countApprovalDriversByStatus('Pending');
            } catch (\Throwable) {
                // Keep menu usable during install / migrate when DB is unavailable.
            }

            foreach ($verticalMenuData->menu as $item) {
                if (($item->slug ?? null) !== 'fleet-approvals') {
                    continue;
                }

                if ($pendingApprovals > 0) {
                    $item->badge = ['danger', (string) $pendingApprovals];
                } else {
                    unset($item->badge);
                }
            }

            $view->with('menuData', $verticalMenuData);
        });
    }
}
