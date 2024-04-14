<?php

declare(strict_types=1);

namespace App\Foo;

final class Bar
{
    public function __construct(
        public readonly Baz $baz,
        public readonly FooInterface $foo,
    ) {
    }
}
