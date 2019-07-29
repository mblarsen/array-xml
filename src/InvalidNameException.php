<?php

namespace ArrayToXML;

use Exception;
use Throwable;

class InvalidNameException extends Exception
{
    /* @var string */
    protected $name;

    public function __construct(string $message, string $name, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

}
