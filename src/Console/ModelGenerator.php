<?php

namespace Etsetra\Elasticsearch\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ModelGenerator extends Command
{
    protected $name;
    protected $class;
    protected $index;
    protected $migration;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:model {name} {--m}?';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Elasticsearch model for Laravel.';

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
        $this->class = Str::ucfirst(Str::camel($this->argument('name')));
        $this->index = Str::slug(Str::kebab($this->class), '_');
        $this->migration = date('Y_m_d_His').'_create_'.Str::plural($this->index).'_table';

        /**
         * Create Model
         */
        try
        {
            file_put_contents("app/Models/$this->class.php", $this->modelContent());

            $this->info('Model created successfully.');
        }
        catch (\Exception $e)
        {
            $this->error($e->getMessage());
        }

        /**
         * Create Migration
         */
        if ($this->option('m'))
        {
            try
            {
                file_put_contents("database/migrations/$this->migration.php", $this->migrationContent());

                $this->info("Created Migration: $this->migration");
            }
            catch (\Exception $e)
            {
                $this->error($e->getMessage());
            }
        }
    }

    /**
     * Model File
     * 
     * @return string
     */
    private function modelContent()
    {
        return "<?php

namespace App\\Models;

use Etsetra\\Elasticsearch\\Model;

class $this->class extends Model
{
    protected \$index = '$this->index';
}
";
    }

    /**
     * Migration File
     * 
     * @return string
     */
    private function migrationContent()
    {
        return "<?php

use Illuminate\\Database\\Migrations\\Migration;

use App\\Models\\$this->class as Model;

class Create".$this->class."Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \$settings = config('elasticsearch.settings');
        \$settings['number_of_shards'] = 2;
        \$settings['number_of_replicas'] = 1;

        \$query = (new Model)->createIndex(
            [
                'properties' => [
                    'id' => [ 'type' => 'keyword' ],

                    //

                    'created_at' => [ 'type' => 'date' ]
                ]
            ],
            \$settings
        );

        print_r(\$query);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \$query = (new Model)->deleteIndex();

        print_r(\$query);
    }
}
";
    }
}
