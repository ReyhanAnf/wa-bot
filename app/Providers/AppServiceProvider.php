<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(\App\Services\CommandRegistry::class, function ($app) {
            $registry = new \App\Services\CommandRegistry();

            // Task
            $registry->register('/tugas', \App\Commands\Task\TugasCommand::class);

            // Utility
            $registry->register('/menu', \App\Commands\Utility\MenuCommand::class);
            $registry->register('/help', \App\Commands\Utility\MenuCommand::class); // Alias
            $registry->register('/jadwal', \App\Commands\Utility\JadwalCommand::class);
            $registry->register('/ai', \App\Commands\Utility\AiCommand::class);
            $registry->register('/cuaca', \App\Commands\Utility\CuacaCommand::class);
            $registry->register('/gempa', \App\Commands\Utility\GempaCommand::class);
            $registry->register('/shortlink', \App\Commands\Utility\ShortlinkCommand::class);
            $registry->register('/kbbi', \App\Commands\Utility\KbbiCommand::class);

            // Fun
            $registry->register('/gombal', \App\Commands\Fun\GombalCommand::class);
            $registry->register('/kerangajaib', \App\Commands\Fun\KerangAjaibCommand::class);
            $registry->register('/kalkulatorcinta', \App\Commands\Fun\KalkulatorCintaCommand::class);
            $registry->register('/cekkhodam', \App\Commands\Fun\CekKhodamCommand::class);

            // Islamic
            $registry->register('/jadwalsholat', \App\Commands\Islamic\JadwalSholatCommand::class);

            return $registry;
        });
    }
}
