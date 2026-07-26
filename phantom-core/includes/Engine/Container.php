<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

interface ContainerInterface
{
    public function get(string $id): mixed;
    public function has(string $id): bool;
}

interface ContainerExceptionInterface {}

interface NotFoundExceptionInterface {}

class ContainerException extends \Exception implements ContainerExceptionInterface {}

class NotFoundException extends \Exception implements NotFoundExceptionInterface {}

class Container implements ContainerInterface
{
    private array $factories = [];
    private array $singletons = [];
    private array $instances = [];
    private array $tags = [];
    private array $resolving = [];

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            return ($this->factories[$id])($this);
        }

        if (isset($this->singletons[$id])) {
            $this->instances[$id] = ($this->singletons[$id])($this);
            return $this->instances[$id];
        }

        if (class_exists($id)) {
            return $this->autoWire($id);
        }

        throw new NotFoundException("No entry or class found for: {$id}");
    }

    public function has(string $id): bool
    {
        if (array_key_exists($id, $this->instances)) {
            return true;
        }
        if (isset($this->factories[$id])) {
            return true;
        }
        if (isset($this->singletons[$id])) {
            return true;
        }
        if (class_exists($id)) {
            return true;
        }
        return false;
    }

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function singleton(string $id, callable $factory): void
    {
        $this->singletons[$id] = $factory;
    }

    public function tag(string $id, string $tag): void
    {
        $this->tags[$tag][] = $id;
    }

    public function tagged(string $tag): array
    {
        if (!isset($this->tags[$tag])) {
            return [];
        }
        $resolved = [];
        foreach ($this->tags[$tag] as $id) {
            $resolved[] = $this->get($id);
        }
        return $resolved;
    }

    public function autoWire(string $class): object
    {
        if (isset($this->resolving[$class])) {
            throw new ContainerException("Circular dependency detected for: {$class}");
        }

        if (!class_exists($class)) {
            throw new NotFoundException("Class not found for auto-wiring: {$class}");
        }

        $ref = new \ReflectionClass($class);

        if (!$ref->isInstantiable()) {
            throw new ContainerException("Class is not instantiable: {$class}");
        }

        $constructor = $ref->getConstructor();

        if ($constructor === null) {
            return $ref->newInstance();
        }

        $params = $constructor->getParameters();
        $args = [];

        $this->resolving[$class] = true;

        try {
            foreach ($params as $param) {
                $type = $param->getType();

                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    $typeName = $type->getName();
                    if ($param->allowsNull()) {
                        $args[] = null;
                    } else {
                        try {
                            $args[] = $this->get($typeName);
                        } catch (NotFoundException $e) {
                            if ($param->isDefaultValueAvailable()) {
                                $args[] = $param->getDefaultValue();
                            } else {
                                throw new ContainerException(
                                    "Cannot resolve parameter \${$param->getName()} of type {$typeName} in {$class}"
                                );
                            }
                        }
                    }
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } elseif ($param->allowsNull()) {
                    $args[] = null;
                } else {
                    throw new ContainerException(
                        "Cannot resolve parameter \${$param->getName()} in {$class} — no type hint and no default value"
                    );
                }
            }
        } finally {
            unset($this->resolving[$class]);
        }

        return $ref->newInstanceArgs($args);
    }
}
