<?php

declare(strict_types=1);

namespace App\Foo;

final class Baz
{
    public function __construct(
        public readonly string $qux = 'ko',
    ) {
    }
}
