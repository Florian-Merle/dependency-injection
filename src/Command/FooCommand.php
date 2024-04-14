<?php

declare(strict_types=1);

namespace App\Command;

use App\Foo\Bar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FooCommand extends Command
{
    public function __construct(
        private readonly Bar $bar,
        private readonly string $myParam,
        private readonly mixed $handler,
    )
    {
        parent::__construct('app:foo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dd($this->handler);

        // $output->writeln($this->bar->baz->qux);
        // $output->writeln($this->myParam);
        //
        // dd($this->bar);

        return self::SUCCESS;
    }
}
