<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('accounts:verify-integrity', function () {
    $this->call(\App\Console\Commands\VerifyAccountsIntegrity::class);
})->purpose('Verify accounts ledger integrity');
