<?php

declare(strict_types=1);

namespace App\DependencyInjection;

final class ContainerHelper
{
    public static function isInterface(string $class): bool
    {
        $reflector = new \ReflectionClass($class);

        return $reflector->isInterface();
    }

    /**
     * @param list<string> $serviceIds
     */
    public static function findImplementationId(string $id, array $serviceIds): ?string
    {
        $implementationMap = self::buildImplementationMap($serviceIds);

        return $implementationMap[$id] ?? null;
    }

    /**
     * @param list<string> $serviceIds
     *
     * @return array<string, ?string>
     */
    private static function buildImplementationMap(array $serviceIds): array
    {
        $map = [];
        foreach ($serviceIds as $id) {
            try {
                $reflector = new \ReflectionClass($id);
            } catch (\Throwable) {
                continue;
            }
            
            if ($reflector->isInterface()) {
                $map[$id] = self::findImplementation($id, $serviceIds);
            }
        }

        return $map;
    }

    /**
     * @param list<string> $serviceIds
     */
    private static function findImplementation(string $interfaceId, array $serviceIds): ?string
    {
        $implementations = [];
        foreach ($serviceIds as $id) {
            try {
                $reflector = new \ReflectionClass($id);
            } catch (\Throwable) {
                continue;
            }

            if ($id !== $interfaceId && $reflector->implementsInterface($interfaceId)) {
                $implementations[] = $id;
            }
        }

        if (count($implementations) === 1) {
            return $implementations[0];
        }

        return null;
    }

    public static function findTaggedServices(string $tag, array $serviceConfigList): array
    {
        return array_filter($serviceConfigList, function (array $serviceConfig) use ($tag): bool {
            return self::getTagConfig($tag, $serviceConfig) !== null;
        });
    }

    public static function getTagConfig(string $tag, array $serviceConfig): ?array
    {
        foreach ($serviceConfig['tags'] ?? [] as $tagConfig) {
            if ($tagConfig['name'] === $tag) {
                return $tagConfig;
            }
        }

        return null;
    }
    
    public static function getDecorationStack(string $id, array $serviceIds, array $serviceConfigList): array
    {
        $decoratedServiceId = $id;
        if (($serviceConfigList['services'][$id]['decorates'] ?? null) !== null) {
            $decoratedServiceId = $serviceConfigList['services'][$id]['decorates'];
        }

        $stack = [
            $decoratedServiceId => $serviceConfigList['services'][$decoratedServiceId],
            ...array_filter($serviceConfigList['services'], function (?array $serviceConfig) use ($id, $decoratedServiceId): bool {
                if (($serviceConfig['decorates'] ?? null) === null) {
                    return false;
                }

                return $decoratedServiceId === $serviceConfig['decorates'];
            }),
        ];

        return $stack;
    }

    public static function isServiceDecorated(string $id, array $serviceIds, array $serviceConfigList): bool
    {
        $decoratedServiceIds = array_unique(array_map(
            fn ($serviceConfig) => $serviceConfig['decorates'] ?? null,
            $serviceConfigList['services'] ?? [],
        ));

        $decoratedServiceIds = array_filter($decoratedServiceIds);

        return in_array($id, $decoratedServiceIds);
    }

    public static function getMainId(string $id, array $serviceIds, array $serviceConfigList): string
    {
        $serviceConfig = $serviceConfigList['services'][$id] ?? [];

        $alias = $serviceConfig['alias'] ?? null;
        if (null !== $alias) {
            return $alias;
        }

        if (self::isServiceDecorated($id, $serviceIds, $serviceConfigList)) {
            $stack = self::getDecorationStack($id, $serviceIds, $serviceConfigList);

            return array_key_last($stack);
        }

        if (self::isInterface($serviceConfig['class'] ?? $id)) {
            $implementationId = self::findImplementationId($id, $serviceIds);
            if ($implementationId === null) {
                throw new ContainerException("No implementation found for $id");
            }

            return $implementationId;
        }

        return $id;
    }
}
