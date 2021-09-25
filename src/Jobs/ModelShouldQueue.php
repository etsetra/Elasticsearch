<?php

namespace Etsetra\Elasticsearch\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Etsetra\Elasticsearch\Client;

class ModelShouldQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $method;
    public $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $method, array $params)
    {
        $this->method = $method;
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new Client)->build()->{$this->method}($this->params);
    }
}
