<?php

namespace Etsetra\Elasticsearch\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

use Etsetra\Elasticsearch\Client;

class DeleteApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:index:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'You can delete Elasticsearch indexes from the console.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $client = (new Client)->build();
        $indices = $client->cat()->indices();

        $this->table(
            [
                'health',
                'status',
                'index',
                'pri',
                'rep',
                'docs.count',
                'docs.deleted',
                'store.size',
                'pri.store.size',
            ],
            $indices
        );

        $name = $this->anticipate('Please select the index you want to delete', Arr::pluck($indices, 'index'));

        if ($name)
        {
            if ($this->confirm('Are you sure?'))
            {
                $password = $this->secret('Password?');

                if ($password == config('elasticsearch.password'))
                {
                    try
                    {
                        $response = $client->indices()->delete(
                            [
                                'index' => $name
                            ]
                        );

                        $this->info(json_encode($response, JSON_PRETTY_PRINT));
                    }
                    catch (\Exception $e)
                    {
                        $this->error($e->getMessage());
                    }
                }
                else $this->error('The password you entered is not valid');
            }
            else $this->line('You stopped deleting the index');
        }
        else $this->error('Please specify an index!');
    }
}
