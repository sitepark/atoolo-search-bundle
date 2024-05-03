<?php

declare(strict_types=1);

namespace Atoolo\Search\Exception;

use Atoolo\Resource\ResourceLanguage;
use RuntimeException;

class UnsupportedIndexLanguageException extends RuntimeException
{
    public function __construct(
        private readonly string $index,
        private readonly ResourceLanguage $lang,
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $index . '/' . $lang->code . ': ' . $message,
            $code,
            $previous
        );
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function getLang(): ResourceLanguage
    {
        return $this->lang;
    }
}
