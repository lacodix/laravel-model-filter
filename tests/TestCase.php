<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Lacodix\LaravelModelFilter\LaravelModelFilterServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use LazilyRefreshDatabase;
    use InteractsWithViews;
    use WithFaker;

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
            $table->integer('counter');
            $table->timestamps();
        });

        Capsule::schema()->create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->nullable();
            $table->string('title');
            $table->boolean('published');
            $table->text('content');
            $table->integer('counter');
            $table->timestamps();
        });

        Capsule::schema()->create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Capsule::schema()->create('post_tag', function (Blueprint $table) {
            $table->foreignId('tag_id');
            $table->foreignId('post_id');
            $table->timestamp('start')->nullable();
            $table->timestamp('end')->nullable();
        });

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Tests\\Database\\Factories\\'.Str::afterLast($modelName, '\\').'Factory'
        );

        Factory::guessModelNamesUsing(
            fn (Factory $factory) => 'Tests\\Models\\'.Str::replaceLast(
                'Factory',
                '',
                Str::afterLast($factory::class, '\\')
            )
        );
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            LaravelModelFilterServiceProvider::class,
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
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
