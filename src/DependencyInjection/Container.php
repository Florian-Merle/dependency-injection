<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionParameter;
use UnitEnum;

final class Container implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $services = [];

    /**
     * @param array<array-key, mixed> $config
     * @param string[] $serviceIds
     * @param string[] $parameterNames
     */
    public function __construct(
        private array $config,
        private array $serviceIds,
        private array $parameterNames,
    ) {
    }

    public function get(string $id): mixed
    {
        if (false === $this->has($id)) {
            throw new NotFoundException();
        }

        if (false === array_key_exists($id, $this->services)) {
            $this->services[$id] =  $this->buildInstance(
                ContainerHelper::getMainId($id, $this->serviceIds, $this->config),
            );
        }

        return $this->services[$id];
    }

    private function buildInstance(string $id): mixed
    {
        $serviceConfig = $this->config['services'][$id] ?? [];

        $factoryConfig = $serviceConfig['factory'] ?? null;
        if ($factoryConfig !== null) {
            $factoryServiceId = substr($factoryConfig[0], 1);
            $factoryMethod = $factoryConfig[1];

            return $this->get($factoryServiceId)->$factoryMethod();
        }

        $reflector = new \ReflectionClass($serviceConfig['class'] ?? $id);
        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Service $id is not instantiable");
        }

        $constructor = $reflector->getConstructor();
        if (null === $constructor || empty($constructor->getParameters())) {
            $instance = $reflector->newInstance();
        } else {
            $parameters = $this->resolveDependencies($id, $constructor->getParameters(), $serviceConfig);
            $instance = $reflector->newInstanceArgs($parameters);
        }

        return $instance;
    }

    /**
     * @param ReflectionParameter[] $constructorParameters
     * @param array<array-key, mixed> $serviceConfig
     *
     * @return list<mixed>
     */
    private function resolveDependencies(string $id, array $constructorParameterList, array $serviceConfig): array
    {
        $dependencies = [];
        foreach ($constructorParameterList as $constructorParameter) {
            $dependencies[] = $this->resolveDependency($id, $constructorParameter, $serviceConfig);
        }

        return $dependencies;
    }

    /**
     * @param array<array-key, mixed> $serviceConfig
     */
    private function resolveDependency(string $id, ReflectionParameter $constructorParameter, array $serviceConfig): mixed
    {
        $value = $serviceConfig['parameters'][$constructorParameter->getPosition()] ?? $serviceConfig['parameters'][$constructorParameter->getName()] ?? null;

        // no config defined for dependency
        if (null === $value) {
            if ($constructorParameter->isDefaultValueAvailable()) {
                return $constructorParameter->getDefaultValue();
            }

            // Needed for autowiring
            try {
                return $this->get($constructorParameter->getType()->__toString());
            } catch(NotFoundExceptionInterface) {
            }

            throw new ContainerException('Cannot autowire parameter ' . $constructorParameter->getName() . ' of type ' . $constructorParameter->getType());
        }

        // inner
        if (str_starts_with($value, '@.inner')) {
            $stack = ContainerHelper::getDecorationStack($id, $this->serviceIds, $this->config);
            $currentPosition = array_search($id, array_keys($stack));

            return $this->buildInstance(array_keys($stack)[$currentPosition - 1]);
        }

        // service
        if (str_starts_with($value, '@')) {
            return $this->get(substr($value, 1));
        }

        // tagged iterator
        if (str_starts_with($value, '!tagged_iterator ')) {
            $tag = substr($value, 17);

            $taggedServiceListConfig = ContainerHelper::findTaggedServices($tag, $this->config['services']);
            $taggedServices = new \SplPriorityQueue();
            foreach ($taggedServiceListConfig as $id => $taggedServiceConfig) {
                $tagConfig = ContainerHelper::getTagConfig($tag, $taggedServiceConfig);

                $taggedServices->insert($this->get($id), $tagConfig['priority'] ?? 0);
            }

            return $taggedServices;
        }

        return $value;
    }

    public function has(string $id): bool
    {
        return in_array($id, $this->serviceIds);
    }

    public function getIds(): array
    {
        return $this->serviceIds;
    }

    public function getParameter(string $name): array|bool|string|int|float|\UnitEnum|null
    {
        if (in_array($name, $this->parameterNames)) {
            return $this->config['parameters'][$name];
        }

        throw new NotFoundException();
    }

    public function hasParameter(string $name): bool
    {
        return in_array($name, $this->parameterNames);
    }
    /**
     * @return string[]
     */
    public function getParameterNames(): array
    {
        return $this->parameterNames;
    }
}
