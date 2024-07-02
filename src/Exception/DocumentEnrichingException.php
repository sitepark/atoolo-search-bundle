<?php

declare(strict_types=1);

namespace Atoolo\Search\Exception;

use Atoolo\Resource\ResourceLocation;
use RuntimeException;

class DocumentEnrichingException extends RuntimeException
{
    public function __construct(
        private readonly ResourceLocation $location,
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $location->__toString() . ': ' . $message,
            $code,
            $previous,
        );
    }

    public function getLocation(): ResourceLocation
    {
        return $this->location;
    }
}
