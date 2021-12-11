<?php

namespace Etsetra\Elasticsearch;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

use Etsetra\Elasticsearch\Jobs\ModelShouldQueue;

class Model
{
    protected $shouldQueue;

    public function __construct()
    {
        $this->shouldQueue = false;
    }

    /**
     * Create item
     * 
     * @param array $body
     * @return object
     */
    public function create(array $body, bool $upsert = false, bool $shouldQueue = false)
    {
        $this->shouldQueue = $shouldQueue;

        return $this->modelBuild(
            $upsert ? 'index' : 'create',
            [
                'index' => $this->index,
                'id' => @$body['id'] ?? date('ynd').Str::random(6).date('his'),
                'body' => $body
            ]
        );
    }

    /**
     * Get item by id
     * 
     * @param string $id
     * @return object
     */
    public function get(string $id)
    {
        try
        {
            $query = (object) (new Client)->build()->get(
                [
                    'index' => $this->index,
                    'id' => $id
                ]
            );

            return (object) [
                'success' => 'ok',
                'source' => (object) $query->_source
            ];
        }
        catch (\Exception $e)
        {
            return $this->modelCatch($e);
        }
    }

    /**
     * Update item
     * 
     * @param string $id
     * @param array $doc
     * @return object
     */
    public function update(string $id, array $doc, bool $shouldQueue = false)
    {
        $this->shouldQueue = $shouldQueue;

        return $this->modelBuild(
            'update',
            [
                'index' => $this->index,
                'id' => $id,
                'body' => [
                    'doc' => $doc
                ]
            ]
        );
    }

    /**
     * Update item by script
     * 
     * @param string $id
     * @param string $script
     * @return object
     */
    public function script(string $id, array $script, bool $shouldQueue = false)
    {
        $this->shouldQueue = $shouldQueue;

        return $this->modelBuild(
            'update',
            [
                'index' => $this->index,
                'id' => $id,
                'body' => [
                    'script' => implode(PHP_EOL, $script)
                ]
            ]
        );
    }

    /**
     * Delete item by id
     * 
     * @param string $id
     * @return object
     */
    public function delete(string $id, bool $shouldQueue = false)
    {
        $this->shouldQueue = $shouldQueue;

        return $this->modelBuild(
            'delete',
            [
                'index' => $this->index,
                'id' => $id
            ]
        );
    }

    /**
     * Delete item(s) by query
     * 
     * @param array $query
     * @return object
     */
    public function deleteByQuery(array $query, bool $shouldQueue = false)
    {
        $this->shouldQueue = $shouldQueue;

        return $this->modelBuild(
            'deleteByQuery',
            [
                'index' => $this->index,
                'body' => [
                    'query' => $query
                ]
            ]
        );
    }

    /**
     * Find item by query
     * 
     * @param array $query
     * @param array $body
     * @return object
     */
    public function find(array $query, array $body = [])
    {
        try
        {
            $query = (new Client)->build()->search(
                [
                    'index' => $this->index,
                    'body' => array_merge(
                        [
                            'query' => $query
                        ],
                        $body
                    )
                ]
            );

            return (object) [
                'success' => 'ok',
                'source' => array_map(
                    function($item) {
                        $item['_source']['id'] = $item['_id'];

                        return $item['_source'];
                    },
                    $query['hits']['hits']
                ),
                'aggregations' => $query['aggregations'] ?? [],
                'stats' => [
                    'total' => $query['hits']['total']['value']
                ]
            ];
        }
        catch (\Exception $e)
        {
            return $this->modelCatch($e);
        }
    }

    /**
     * Create index for this model
     * 
     * @param array $mappings
     * @param array $settings
     * @return object
     */
    public function createIndex(array $mappings, array $settings = [])
    {
        try
        {
            return (new Client)->build()->indices()->create(
                [
                    'index' => $this->index,
                    'body' => [
                        'mappings' => $mappings,
                        'settings' => array_merge(config('elasticsearch.settings'), $settings),
                    ]
                ]
            );
        }
        catch (\Exception $e)
        {
            return $this->modelCatch($e);
        }
    }

    /**
     * Delete to this index
     * 
     * @return object
     */
    public function deleteIndex()
    {
        try
        {
            return (new Client)->build()->indices()->delete(
                [
                    'index' => $this->index
                ]
            );
        }
        catch (\Exception $e)
        {
            return $this->modelCatch($e);
        }
    }

    /**
     * Put index settings
     * 
     * @param array $params
     * @return object
     */
    public function putIndexSettings(array $params)
    {
        try
        {
            return (new Client)->build()->indices()->putSettings(
                [
                    'index' => $this->index,
                    'body' => [
                        'settings' => $params
                    ]
                ]
            );
        }
        catch (\Exception $e)
        {
            return $this->modelCatch($e);
        }
    }

    /**
     * Put index mapping
     * 
     * @param array $params
     * @return object
     */
    public function putIndexMapping(array $params)
    {
        try
        {
            return (new Client)->build()->indices()->putMapping(
                [
                    'index' => $this->index,
                    'body' => [
                        '_source' => [
                            'enabled' => true
                        ],
                        'properties' => $params
                    ]
                ]
            );
        }
        catch (\Exception $e)
        {
            return $this->modelCatch($e);
        }
    }

    /**
     * Get this index settings
     * 
     * @return object
     */
    public function getIndexSettings()
    {
        try
        {
            return (new Client)->build()->indices()->getSettings(
                [
                    'index' => $this->name
                ]
            );
        }
        catch (\Exception $e)
        {
            return (object) [
                'success' => 'failed',
                'log' => $e->getMessage()
            ];
        }
    }

    /**
     * Get this index status
     * 
     * @param bool $action
     * @return object
     */
    public function indexStatus(bool $action)
    {
        try
        {
            return (new Client)->build()->indices()->{$action ? 'open' : 'close'}(
                [
                    'index' => $this->name
                ]
            );
        }
        catch (\Exception $e)
        {
            return $this->modelCatch($e);
        }
    }

    ###########################################################
    ######################## UTILITIES ########################
    ##                                                       ##

    /**
     * Catch
     * 
     * @param \Exception $e
     * @return object
     */
    private function modelCatch($e)
    {
        return (object) [
            'success' => 'failed',
            'log' => json_decode($e->getMessage())
        ];
    }

    /**
     * Should Queue
     * 
     * @param string $method
     * @param array $params
     * @return object
     */
    private function modelBuild(string $method, array $params)
    {
        if ($this->shouldQueue)
            ModelShouldQueue::dispatch($method, $params)->onQueue('elasticsearch');
        else
        {
            try
            {
                $query = (new Client)->build()->{$method}($params);

                return (object) [
                    'success' => 'ok',
                    'log' => $query
                ];
            }
            catch (\Exception $e)
            {
                return $this->modelCatch($e);
            }
        }

        return (object) [
            'success' => 'ok',
            'log' => 'onQueue'
        ];
    }

    ##                                                       ##
    ###########################################################
}
