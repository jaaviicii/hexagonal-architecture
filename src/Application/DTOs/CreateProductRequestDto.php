<?php

namespace App\Application\DTOs;

use App\Entity\ProductId;
use DateTime;

class CreateProductRequestDto
{
    private $id;

    private $name;

    private $reference;

    private $createdAt;

    public function __construct(ProductId $id, string $name, string $reference, DateTime $createdAt)
    {
        $this->id = $id;
        $this->name = $name;
        $this->reference = $reference;
        $this->createdAt = $createdAt;
    }

    public function id(): ProductId
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
