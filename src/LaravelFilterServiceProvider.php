<?php

namespace Lacodix\LaravelFilter;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Lacodix\LaravelFilter\Commands\MakeFilterCommand;

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
