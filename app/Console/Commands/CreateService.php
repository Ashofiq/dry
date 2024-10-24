<?php

namespace App\Console\Commands;

/** Dev: Ahmad Shafik Git: Ashofiq */

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class CreateService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {serviceName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Service Class';

    /**
     * Execute the console command.
     *
     * @return int
     */
    // public function handle() {
    // 	$serviceName = $this->arguments()['serviceName'];
    // 	if ( !file_exists( 'app/Services' ) ) {
    // 		return mkdir( 'app/Services' );
    // 	}
    // 	app_path( 'Console/Stubs/service.stub' );
    // 	return $this->info( "{$serviceName}" );
    // }

    protected $files;

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->getSourceFilePath();

        $this->makeDirectory(dirname($path));

        $contents = $this->getSourceFile();

        if (! $this->files->exists($path)) {
            $this->files->put($path, $contents);
            $this->info("File : {$path} created");
        } else {
            $this->info("File : {$path} already exits");
        }

    }

    /**
     * Return the stub file path
     *
     * @return string
     */
    public function getStubPath()
    {
        return base_path().'/app/console/stubs/service.stub';
    }

    /**
     **
     * Map the stub variables present in stub to its value
     *
     * @return array
     */
    public function getStubVariables()
    {
        return [
            'NAMESPACE' => 'App\\Interfaces',
            'CLASS_NAME' => $this->getSingularClassName($this->argument('serviceName')),
        ];
    }

    /**
     * Get the stub path and the stub variables
     *
     * @return bool|mixed|string
     */
    public function getSourceFile()
    {
        return $this->getStubContents($this->getStubPath(), $this->getStubVariables());
    }

    /**
     * Replace the stub variables(key) with the desire value
     *
     * @param  array  $stubVariables
     * @return bool|mixed|string
     */
    public function getStubContents($stub, $stubVariables = [])
    {
        $contents = file_get_contents($stub);
        $contents = str_replace('DummyClass', $this->getClassName(), $contents);

        foreach ($stubVariables as $search => $replace) {
            $contents = str_replace('$'.$search.'$', $replace, $contents);
        }

        return $contents;

    }

    /**
     * Get the class name
     *
     * @return string
     */
    public function getClassName()
    {
        $replace = explode('/', $this->argument('serviceName'));

        return $replace[count($replace) - 1];
    }

    /**
     * Get the full path of generate class
     *
     * @return string
     */
    public function getSourceFilePath()
    {
        return base_path('App\\Services').'\\'.$this->getSingularClassName($this->argument('serviceName')).'.php';
    }

    /**
     * Return the Singular Capitalize Name
     *
     * @return string
     */
    public function getSingularClassName($name)
    {
        return ucwords($name);
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0777, true, true);
        }

        return $path;
    }
}