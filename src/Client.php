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

    /**
     * Elasticsearch Cat Api
     * 
     * @param string $module
     * @param array $params
     * @return object
     */
    public function cat(string $module, array $params = [])
    {
        $prefix = config('elasticsearch.prefix');

        try
        {
            $client = self::build();

            switch ($module)
            {
                case 'indices':
                    $params = array_merge([ 'index' => ($prefix ? $prefix.'__*' : '*'), 's' => 'index:desc' ], $params);
                    $data = array_map(function($line) use($prefix) {
                        $line['index'] = str_replace($prefix.'__', '', $line['index']);

                        return $line;
                    }, $client->cat()->indices($params));
                break;
                case 'health':
                    $params = array_merge([ 's' => 'status:asc' ], $params);
                    $data = $client->cat()->health($params);
                break;
                case 'nodes':
                    $params = array_merge([ 's' => 'name:asc' ], $params);
                    $data = $client->cat()->nodes($params);
                break;
            }

            return (object) [
                'success' => 'ok',
                'data' => $data
            ];
        }
        catch (\Exception $e)
        {
            return (object) [
                'success' => 'failed',
                'log' => $e->getMessage()
            ];
        }
    }
}
