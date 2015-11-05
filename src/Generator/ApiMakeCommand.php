<?php
/**
 * Created by PhpStorm.
 * User: pkfrom
 * Date: 5/11/2558
 * Time: 8:30
 */

namespace Fromz\Api\Generator;

use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class ApiMakeCommand extends Command
{
    use AppNamespaceDetectorTrait;

    protected $files;

    protected $name = 'make:api';

    protected $description = 'Create api controller, transformer and api routes for a given model';

    protected $stubVariables = [
        'app'         => [],
        'model'       => [],
        'controller'  => [],
        'transformer' => [],
        'route'       => [],
    ];

    protected $modelsBaseNamespace;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function fire()
    {
        $this->prepareVariablesForStubs($this->argument('name'));

        $this->createController();

        $this->createTransformer();

        $this->addRoutes();
    }

    protected function prepareVariablesForStubs($name)
    {
        $this->stubVariables['app']['namespace'] = $this->getAppNamespace();

        $baseDir = config('l5-api.models_base_dir');

        $this->modelsBaseNamespace = $baseDir ? trim($baseDir, '\\').'\\' : '';

        $this->setModelData($name)
            ->setControllerData()
            ->setRouteData()
            ->setTransformerData();
    }

    protected function setModelData($name)
    {
        if (str_contains($name, '/')) {
            $name = $this->convertSlashes($name);
        }

        $name = trim($name, '\\');

        $this->stubVariables['model']['fullNameWithoutRoot'] = $name;
        $this->stubVariables['model']['fullName'] = $this->stubVariables['app']['namespace'].$this->modelsBaseNamespace.$name;

        $exploded = explode('\\', $this->stubVariables['model']['fullName']);
        $this->stubVariables['model']['name'] = array_pop($exploded);
        $this->stubVariables['model']['namespace'] = implode('\\', $exploded);

        $exploded = explode('\\', $this->stubVariables['model']['fullNameWithoutRoot']);
        array_pop($exploded);
        $this->stubVariables['model']['additionalNamespace'] = implode('\\', $exploded);

        return $this;
    }

    protected function setControllerData()
    {
        return $this->setDataForEntity('controller');
    }

    protected function setRouteData()
    {
        $name = str_replace('\\', '', $this->stubVariables['model']['fullNameWithoutRoot']);
        $name = snake_case($name);

        $this->stubVariables['route']['name'] = str_plural($name);

        return $this;
    }

    protected function setTransformerData()
    {
        return $this->setDataForEntity('transformer');
    }

    protected function setDataForEntity($entity)
    {
        $entityNamespace = $this->convertSlashes(config("l5-api.{$entity}s_dir"));
        $this->stubVariables[$entity]['name'] = $this->stubVariables['model']['name'].ucfirst($entity);

        $this->stubVariables[$entity]['namespaceWithoutRoot'] = implode('\\', array_filter([
            $entityNamespace,
            $this->stubVariables['model']['additionalNamespace'],
        ]));

        $this->stubVariables[$entity]['namespaceBase'] = $this->stubVariables['app']['namespace'].$entityNamespace;

        $this->stubVariables[$entity]['namespace'] = $this->stubVariables['app']['namespace'].$this->stubVariables[$entity]['namespaceWithoutRoot'];

        $this->stubVariables[$entity]['fullNameWithoutRoot'] = $this->stubVariables[$entity]['namespaceWithoutRoot'].'\\'.$this->stubVariables[$entity]['name'];

        $this->stubVariables[$entity]['fullName'] = $this->stubVariables[$entity]['namespace'].'\\'.$this->stubVariables[$entity]['name'];

        return $this;
    }

    /**
     *  Create controller class file from a stub.
     */
    protected function createController()
    {
        $this->createClass('controller');
    }

    /**
     *  Create controller class file from a stub.
     */
    protected function createTransformer()
    {
        $this->createClass('transformer');
    }

    /**
     *  Add routes to routes file.
     */
    protected function addRoutes()
    {
        $stub = $this->constructStub(base_path(config('l5-api.route_stub')));

        $routesFile = app_path(config('l5-api.routes_file'));

        // read file
        $lines = file($routesFile);
        $lastLine = trim($lines[count($lines) - 1]);

        // modify file
        if (strcmp($lastLine, '});') === 0) {
            $lines[count($lines) - 1] = '    '.$stub;
            $lines[] = "\r\n});\r\n";
        } else {
            $lines[] = "$stub\r\n";
        }

        // save file
        $fp = fopen($routesFile, 'w');
        fwrite($fp, implode('', $lines));
        fclose($fp);

        $this->info('Routes added successfully.');
    }


    protected function createClass($type)
    {
        $path = $this->getPath($this->stubVariables[$type]['fullNameWithoutRoot']);
        if ($this->files->exists($path)) {
            $this->error(ucfirst($type).' already exists!');

            return;
        }

        $this->makeDirectoryIfNeeded($path);

        $this->files->put($path, $this->constructStub(base_path(config('l5-api.'.$type.'_stub'))));

        $this->info(ucfirst($type).' created successfully.');
    }


    protected function getPath($name)
    {
        $name = str_replace($this->stubVariables['app']['namespace'], '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'.php';
    }


    protected function makeDirectoryIfNeeded($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }


    protected function constructStub($path)
    {
        $stub = $this->files->get($path);

        foreach ($this->stubVariables as $entity => $fields) {
            foreach ($fields as $field => $value) {
                $stub = str_replace("{{{$entity}.{$field}}}", $value, $stub);
            }
        }

        return $stub;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the model'],
        ];
    }


    protected function convertSlashes($string)
    {
        return str_replace('/', '\\', $string);
    }
}
