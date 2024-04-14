<?php

declare(strict_types=1);

namespace App\DependencyInjection;

final class ContainerBuilderHelper
{
    public static function findAutowirableServiceIds(array $config): array
    {
        $serviceConfigList = $config['autowiring'] ?? [];
        $ids = [];

        foreach ($serviceConfigList as $id => $serviceConfig) {
            // Needed for autowiring
            if (false === array_key_exists('resource', $serviceConfig ?? [])) {
                continue;
            }

            $dir = __DIR__ . '/../' . $serviceConfig['resource'];
            $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            /** @var \SplFileInfo $file */
            foreach ($rii as $file) {
                if ($file->isDir() || $file->getExtension() !== 'php') {
                    continue;
                }

                foreach ($serviceConfig['exclude'] as $excludedPath) {
                    if (str_starts_with($file->getPathname(), __DIR__ . '/../' . $excludedPath)) {
                        continue 2;
                    }
                }

                $pathname = $file->getPathname();
                // remove dir name and extension from pathname, replace / by \
                $ids[] = $id . str_replace(
                    '/',
                    '\\',
                    substr(substr($pathname, strlen($dir)), 0, -4),
                );
            }
        }

        return array_unique($ids);
    }

    public static function findDecoratingServiceIds(array $config): array
    {
        return array_keys(array_filter(
            $config['services'] ?? [],
            fn ($serviceConfig): bool => array_key_exists('decorates', $serviceConfig ?? []),
        ));
    }
}
