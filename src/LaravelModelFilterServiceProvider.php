<?php

namespace Lacodix\LaravelModelFilter;

use Lacodix\LaravelModelFilter\Commands\MakeFilterCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelModelFilterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-model-filter')
            ->hasConfigFile()
            ->hasViews('lacodix-filter')
            ->hasTranslations()
            ->hasCommand(MakeFilterCommand::class);
    }
}
