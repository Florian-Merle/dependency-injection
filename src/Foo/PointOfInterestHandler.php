<?php

declare(strict_types=1);

namespace App\Foo;

final class PointOfInterestHandler
{
    // /** @param iterable<HandlerInterface> $handlers */
    // public function __construct(
    //     private iterable $handlers,
    // ) {
    // }
    //
    // public function handle(PointOfInterest $pointOfInterest): void
    // {
    //     foreach ($this->handlers as $handler) {
    //         if ($handler->can($pointOfInterest)) {
    //             return $handler->handle($pointOfInterest)
    //         }
    //     }
    //
    //     throw new \LogicException("No handler found");
    // }
}

// interface HandlerInterface
// {
//     public function can(PointOfInterest $pointOfInterest): bool;
//
//     public function handle(PointOfInterest $pointOfInterest): mixed;
// }
