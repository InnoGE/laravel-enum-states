<?php

namespace InnoGE\LaravelEnumStates\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use InnoGE\LaravelEnumStates\LaravelEnumStatesServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('orders');
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->nullable();
        });

        Schema::dropIfExists('plain_orders');
        Schema::create('plain_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->nullable();
        });

        Schema::dropIfExists('multi_status_orders');
        Schema::create('multi_status_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->nullable();
            $table->string('review_status')->nullable();
        });

        Schema::dropIfExists('multi_pure_orders');
        Schema::create('multi_pure_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->nullable();
            $table->string('review_status')->nullable();
        });

        Schema::dropIfExists('no_default_orders');
        Schema::create('no_default_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->nullable();
        });

        Schema::dropIfExists('pure_orders');
        Schema::create('pure_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->nullable();
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelEnumStatesServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
