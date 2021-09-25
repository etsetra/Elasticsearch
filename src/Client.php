<?php

namespace Etsetra\Elasticsearch;

use Elasticsearch\ClientBuilder;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Client
{
    /**
     * Logger
     * 
     * @return logger
     */
    private function logger()
    {
        return (new Logger(config('app.name')))->pushHandler(new StreamHandler(storage_path('logs/elasticsearch.log'), Logger::INFO));
    }

    /**
     * Elasticsearch Connection
     * 
     * @return ClientBuilder
     */
    public function build()
    {
        return ClientBuilder::create()
            ->setHosts(config('elasticsearch.servers'))
            ->setRetries(config('elasticsearch.retries'))
            ->setLogger($this->logger())
            ->build();
    }
}
