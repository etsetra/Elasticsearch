# Elasticsearch

### Installation
    composer require etsetra/elasticsearch

##### Create config file

    $ php artisan vendor:publish --tag="etsetra-elasticsearch-config"

##### Update .env file

    ELASTICSEARCH_SERVERS=127.0.0.1:9200,127.0.0.1:9201,127.0.0.1:9202
    ELASTICSEARCH_RETRIES=2
    ELASTICSEARCH_PASSWORD=1234 //This password is unique to you. Used to delete index.

##### Add schedule for bulk actions stack -> App/Console/Kernel.php
    $schedule->command('elasticsearch:bulk:insert')
             ->everyMinute()
             ->runInBackground()
             ->withoutOverlapping(1);

##### Run a queue for should actions in supervisor
    $ php artisan queue:work --queue=elasticsearch

### Model & Migration
    $ php artisan elasticsearch:model MyModel

    // Model created successfully.

or with migration

    $ php artisan elasticsearch:model MyModel --m

    // Model created successfully.

    // Created Migration: 2021_09_25_151308_create_my_model_table

    $ php artisan migrate

You can enter standard elasticsearch mapping parameters into the created migration file.

### Delete Index
    $ php artisan elasticsearch:index:delete
![resim](https://user-images.githubusercontent.com/40306558/134776544-d1311bc5-24f0-4e65-ba01-2e2174531acb.png)

The password is the ELASTICSEARCH_PASSWORD value in the env file.

### Search Document
    use App\Models\MyModel;

    $data = (new MyModel)->find(
        [
            'bool' => [
                'filter' => [
                    'terms' => [
                        'user_id' => [ 1 ]
                    ]
                ]
            ]
        ],
        [
            'from' => 0,
            'size' => 10,
            'sort' => [
                [
                    'created_at' => [
                        'order' => 'desc'
                    ]
                ]
            ]
        ]
    );

    // You can use all parameters of Elasticsearch.

    // Results
    stdClass Object
    (
        [success] => ok
        [source] => Array
            (
                [0] => Array
                    (
                        [id] => J6SzTtlUFpTQ
                        [user_id] => 1
                        [description] => 3 - Dolor egestas velit ligula nunc tortor ultricies quam consequat hac inceptos congue ullamcorper nisl.
                        [created_at] => 2021-09-25T15:23:25+00:00
                        [lang] => en
                    )

                [1] => Array
                    (
                        [id] => tky4EtIvlp1b
                        [user_id] => 1
                        [description] => Suspendisse ante commodo duis dignissim, elit mi orci vulputate hac curabitur duis dignissim
                        [created_at] => 2021-09-25T15:23:25+00:00
                        [lang] => en
                    )
            )

        [aggregations] => Array
            (
            )

        [stats] => Array
            (
                [total] => 2
            )
    )

### Create & Update Document
    use App\Models\MyModel;

    // Create document
    $create = (new MyModel)->create(
        [
            'id' => 'abcd1234',
            'user_id' => 1,
            'description' => 'Lorem ipsum...',
            'created_at' => '2021-09-25T15:23:25+00:00',
            'lang' => 'en',
        ],
        false, // upsert (bool, default = false)
        false, // should queue (bool, default = false)
    );

    // Update document
    $update = (new MyModel)->update(
        'my_doc_id',
        [
            'description' => 'Lorem ipsum text...',
        ],
        false, // should queue (bool, default = false)
    );

    // Update by script
    $update = (new MyModel)->script(
        'my_doc_id',
        [
            'ctx._source.views = 0;',
        ],
        false, // should queue (bool, default = false)
    );

### Delete Document
    use App\Models\MyModel;
    
    $delete = (new MyModel)->delete(
        'my_doc_id',
        false, // should queue (bool, default = false)
    );

    // Delete by Query
    $items = (new MyModel)->deleteByQuery(
        [
          'bool' => [
            'must' => [
              [
                'match' => [ 'user_id' => 1 ]
              ]
            ]
          ]
        ],
        true // shouldQueue
    );

### Get Document
    use App\Models\MyModel;
    
    $item = (new MyModel)->get('my_doc_id');
    
    // Results
    stdClass Object
    (
        [success] => ok
        [source] => stdClass Object
            (
                [id] => J6SzTtlUFpTQFUm5LABP
                [user_id] => 1
                [description] => 'Lorem text...',
                [likes] => 0
                [created_at] => 2021-09-25T15:23:25+00:00
            )

    )

### Bulk Actions
    use Etsetra\Elasticsearch\Console\BulkApi;
    
    BulkApi::chunk(
      'my_model', // index name
      'J6SzTtlUFpTQ', // doc id
      [ 'ctx._source.views += 1' ], // doc body
      'script' // action type
    );
    
    // Action Types;
    // script: elasticsearch java scripts,
    // index: upsert document,
    // create: create document,
    
    // Alternate
    BulkApi::chunk(
      'my_model',
      'J6SzTtlUFpTQ',
      [
        'user_id' => 2,
        'video_id' => 'dummy1234'
      ],
      'index' // action type
    );

### Database status (_cat api)
    use Etsetra\Elasticsearch\Client;

    $query = (new Client)->cat('nodes'); // params: health, indices, nodes
