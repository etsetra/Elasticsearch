<?php

namespace Etsetra\Elasticsearch\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

use Etsetra\Library\DateTime;
use Etsetra\Elasticsearch\Client;

class BulkApi extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'elasticsearch:bulk:insert';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'For Elasticsearch, it takes the data accumulated in Redis to Elasticsearch.';

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
		$redis_keys = Redis::keys('elasticsearch:chunk:*');

		foreach ($redis_keys as $key)
		{
			$split = explode(':', $key);

			if ($datetime = @$split[2])
			{
				if ((new DateTime)->createFromFormat('ymdHi', $datetime) == (new DateTime)->nowAt(date('Y-m-d H:i')))
					$this->error("$datetime: It's not time");
				else
				{
					$key = str_replace(config('database.redis.options.prefix'), '', $key);

					$this->info("$key: Submitted to Elasticsearch");

					$chunk = Redis::get($key);
					$bulk = $this->bulkInsert(explode(PHP_EOL, $chunk));
					$delete = Redis::del($key);
				}
			}
			else $this->error('Bad redis key!');
		}
	}

	/**
	 * @param $index string | İşlemin yapılacağı index
	 * @param $id string | İşlem yapılacak kayıt id
	 * @param $data array | İşlem yapılacak sorgu
	 * @param $action string | index,create,script,delete
	 * @return mixed
	 */
	public static function chunk(string $index, string $id, array $data, string $action = 'create')
	{
		$lines[] = [
			str_replace('script', 'update', $action) => [
				'_index' => $index,
				'_id' => $id,
			]
		];

		switch ($action)
		{
			case 'create':
			case 'index':
				$lines[] = $data;
			break;
			case 'update':
				$lines[] = [ 'doc' => $data ];
			break;
			case 'script':
				foreach ($data as $script)
				{
					$lines[] = [ 'script' => $script ];
				}
			break;
			case 'delete':
			break;
		}

		$lines = array_map(function($line) {
			return json_encode($line, JSON_PRESERVE_ZERO_FRACTION);
		}, $lines);

		$lines = implode(PHP_EOL, $lines);

		$redis_key = 'elasticsearch:chunk:'.date('ymdHi');

		Redis::append($redis_key, $lines.PHP_EOL);
		Redis::expire($redis_key, 3600);
	}

	/**
	 * Bulk insert
	 * 
	 * @param array $params
	 * @return object
	 */
	public static function bulkInsert(array $params)
	{
		try
		{
			return (new Client)->build()->bulk(
				[
					'body' => $params
				]
			);
		}
		catch (\Exception $e)
		{
			return $e;
		}
	}
}
