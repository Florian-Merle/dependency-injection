<?php

declare(strict_types=1);

namespace App\DependencyInjection;


final class ContainerBuilder
{
    public function buildContainer(): Container
    {
        $config = \yaml_parse_file(__DIR__ . '/../../config/custom_services.yaml');

        return new Container(
            $config,
            $this->resolveServiceIds($config),
            array_keys($config['parameters']),
        );
    }

    /**
     * @param string[] $config
     */
    private function resolveServiceIds(array $config): array
    {
        $serviceConfigList = $config['services'] ?? [];
        $ids = [
            ...array_keys($serviceConfigList),
            ...ContainerBuilderHelper::findAutowirableServiceIds($config),
        ];

        return array_unique(array_diff(
            $ids,
            ContainerBuilderHelper::findDecoratingServiceIds($config),
        ));
    }

}
