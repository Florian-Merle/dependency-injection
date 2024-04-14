<?php

declare(strict_types=1);

namespace App\Foo;

final class Factory
{
    public function create(): Client
    {
        return new Client('url');
    }
}
