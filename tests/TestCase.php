<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Lacodix\LaravelFilter\LaravelFilterServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'username' => 'root',
            'password' => '',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::schema()->create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type');
            $table->boolean('published');
            $table->text('content');
            $table->timestamps();
        });

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            return 'Tests\\Database\\Factories\\' . Str::afterLast($modelName, '\\') . 'Factory';
        });

        Factory::guessModelNamesUsing(function (Factory $factory) {
            return 'Tests\\Models\\' . Str::replaceLast(
                'Factory', '', Str::afterLast(get_class($factory), '\\')
            );
        });
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            LaravelFilterServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
