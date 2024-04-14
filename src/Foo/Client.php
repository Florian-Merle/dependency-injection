<?php

declare(strict_types=1);

namespace App\Foo;

final class Client
{
    public function __construct(
        public string $url,
    ) {
    }
}
