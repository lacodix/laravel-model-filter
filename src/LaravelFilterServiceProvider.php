<?php

namespace Lacodix\LaravelFilter;

use Lacodix\LaravelFilter\Commands\MakeFilterCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelFilterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-filter')
            //->hasConfigFile()
            //->hasViews()
            ->hasCommand(MakeFilterCommand::class);
    }
}
