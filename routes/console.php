<?php

use App\Jobs\ExpireBroadcastOffersJob;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cloudways cron should hit `schedule:run` once a minute; that minute tick
// is also what should be draining `queue:work --stop-when-empty` for
// DispatchStoreOrdersJob / BroadcastOrderJob. This entry is the safety net
// that expires stale broadcast offers and retries broadcasting anything
// left stranded without a driver.
Schedule::job(new ExpireBroadcastOffersJob)->everyMinute()->name('expire-broadcast-offers')->withoutOverlapping();
