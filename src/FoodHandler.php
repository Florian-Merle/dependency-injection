<?php

declare(strict_types=1);

namespace App;

final class FoodHandler
{
    public function __construct(
        private $nextHandler,
    ) {
    }

    public function handle($pointOfInterest): mixed
    {
        // if ($pointOfInterest instanceof Food) {
        // 	// do stuff
        //
        // 	return 'food';
        // }

        if ($this->nextHandler) {
            return $this->nextHandler->handle($pointOfInterest);
        }

        throw new \LogicException("No handler found");   
    }
}
