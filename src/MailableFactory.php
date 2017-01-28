<?php

namespace Spatie\MailableTest;

use Exception;
use ReflectionClass;
use ReflectionParameter;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Model;

class MailableFactory
{
    /** @var string */
    public $mailableClass;

    public static function create(string $mailableClass): Mailable
    {
        return (new static ($mailableClass))->getInstance();
    }

    public function __construct(string $mailableClass)
    {
        if (! class_exists($mailableClass)) {
            throw new Exception("Mailable `{$mailableClass}` does not exist.");
        }

        $this->mailableClass = $mailableClass;
    }

    public function getInstance(): Mailable
    {
        $argumentValues = $this->getArguments();

        return new $this->mailableClass(...$argumentValues);
    }

    public function getArguments()
    {
        $parameters = (new ReflectionClass($this->mailableClass))
            ->getConstructor()
            ->getParameters();

        return collect($parameters)
            ->map(function (ReflectionParameter $reflectionParameter) {
                return $this->getArgumentValue(
                    $reflectionParameter->getType()->getName()
                );
            });
    }

    public function getArgumentValue(string $type, string $argumentName)
    {
        if ($type === 'int') {
            return faker()->numberBetween(1, 100);
        }

        if ($type === 'string') {
            return faker()->sentence();
        }

        if ($type === 'bool') {
            return faker()->sometimes();
        }

        $argumentValue = app($type);

        if ($argumentValue instanceof Model) {
            $argumentValue = $this->getModelInstance($argumentValue);
        }

        return $argumentValue;
    }

    public function getModelInstance(Model $model)
    {
        $model = $model->first();

        if (! $model) {
            $modelClass = get_class($model);
            throw new Exception("Could not find a model of class `{$modelClass}`.");
        }

        return $model;
    }
}