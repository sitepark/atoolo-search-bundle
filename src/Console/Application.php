<?php

declare(strict_types=1);

namespace Atoolo\Search\Console;

use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct(iterable $commands = [])
    {
        parent::__construct();
        foreach ($commands as $command) {
            $this->add($command);
        }
    }
}
