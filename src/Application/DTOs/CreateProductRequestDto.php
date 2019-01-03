<?php

namespace App\Application\DTOs;

use DateTime;
use Ramsey\Uuid\Uuid;

class CreateProductRequestDto
{
    private $id;

    private $name;

    private $reference;

    private $createdAt;

    public function __construct(Uuid $id, string $name, string $reference, DateTime $createdAt)
    {
        $this->id = $id;
        $this->name = $name;
        $this->reference = $reference;
        $this->createdAt = $createdAt;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function createdAt(): DateTime
    {
        return $this->createdAt;
    }
}
