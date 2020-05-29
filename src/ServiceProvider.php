<?php

namespace Statamic\Migrator;

use Illuminate\Console\Application as Artisan;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        Commands\MigrateAssetContainer::class,
        Commands\MigrateCollection::class,
        Commands\MigrateFieldset::class,
        Commands\MigrateFieldsetPartial::class,
        Commands\MigrateForm::class,
        Commands\MigrateGlobalSet::class,
        Commands\MigrateGroups::class,
        Commands\MigratePages::class,
        Commands\MigrateRoles::class,
        Commands\MigrateSettings::class,
        Commands\MigrateSite::class,
        Commands\MigrateTaxonomy::class,
        Commands\MigrateTheme::class,
        Commands\MigrateUser::class,
    ];

    public function boot()
    {
        Artisan::starting(function ($artisan) {
            $artisan->resolveCommands($this->commands);
        });
    }
}
