<?php

namespace Etsetra\Elasticsearch\Providers;

use Illuminate\Support\ServiceProvider;

class ElasticsearchServiceProvider extends ServiceProvider
{
    protected $commands = [
        \Etsetra\Elasticsearch\Console\BulkApi::class,
        \Etsetra\Elasticsearch\Console\DeleteApi::class,
        \Etsetra\Elasticsearch\Console\ModelGenerator::class,
    ];

    public function boot()
    {
        //
    }

    public function register()
    {
        $this->commands($this->commands);
        $this->publishes([
            __DIR__.'/../../config/elasticsearch.php' => config_path('elasticsearch.php'),
        ], 'etsetra-elasticsearch-config');
    }
}
