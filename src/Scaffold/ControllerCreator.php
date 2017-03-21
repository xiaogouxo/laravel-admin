<?php

namespace Encore\Admin\Scaffold;

class ControllerCreator
{
    /**
     * Controller full name.
     *
     * @var string
     */
    protected $name;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * ControllerCreator constructor.
     *
     * @param string $name
     * @param null   $files
     */
    public function __construct($name, $files = null)
    {
        $this->name = $name;

        $this->files = $files ?: app('files');
    }

    /**
     * Create a controller.
     *
     * @param string $model
     *
     * @param array $fields
     *
     * @throws \Exception
     *
     * @return string
     */
    public function create($model,$fields)
    {
        $path = $this->getpath($this->name);

        if ($this->files->exists($path)) {
            throw new \Exception("Controller [$this->name] already exists!");
        }

        $stub = $this->files->get($this->getStub());

        $stub = $this->replaceGridAndForm($stub,$fields);

        $this->files->put($path, $this->replace($stub, $this->name, $model));

        return $path;
    }

    /** build grid fields
     * @param $fields
     * @return string
     */
    protected function buildGrid($fields)
    {
        $fields = $this->getFields($fields);

        $column = '';

        foreach ($fields as $field) {
            if (array_get($field, 'grid') == 'on') {
                $column .= "\$grid->{$field['name']}(";

                if (isset($field['comment']) && $field['comment']) {
                    $column .= "'{$field['comment']}'";
                }

                $column .=");\n";
            }
        }
        return $column;
    }

    /** build form fields
     * @param $fields
     * @return string
     */
    protected function buildForm($fields)
    {
        $fields = $this->getFields($fields);
        $column = '';

        foreach ($fields as $field) {
            if (array_get($field, 'form') == 'on') {
                $column .= "\$form->text('{$field['name']}','{$field['comment']}');\n";
            }
        }
        return $column;
    }

    /** get validated fields
     * @param $fields
     * @return array
     */
    protected function getFields($fields)
    {
        $fields = array_filter($fields, function ($field) {
            return isset($field['name']) && !empty($field['name']);
        });
        return $fields;
    }

    /**
     * @param string $stub
     * @param string $name
     * @param string $model
     *
     * @return string
     */
    protected function replace($stub, $name, $model)
    {
        $stub = $this->replaceClass($stub, $name);

        return str_replace(
            ['DummyModelNamespace', 'DummyModel'],
            [$model, class_basename($model)],
            $stub
        );
    }

    /**
     * Get controller namespace from giving name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param string $stub
     * @param string $name
     *
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        return str_replace(['DummyClass', 'DummyNamespace'], [$class, $this->getNamespace($name)], $stub);
    }

    /**
     * Get file path from giving controller name.
     *
     * @param $name
     *
     * @return string
     */
    public function getPath($name)
    {
        $segments = explode('\\', $name);

        array_shift($segments);

        return app_path(implode('/', $segments)).'.php';
    }

    /**
     * Get stub file path.
     *
     * @return string
     */
    public function getStub()
    {
        return __DIR__.'/../Commands/stubs/controller.stub';
    }
}
