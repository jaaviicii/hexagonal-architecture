<?php

namespace App\Application\DTOs;


class CreateProductResponseDto
{
    private $id;
    private $name;
    private $reference;
    private $createdAt;

    public function __construct(
        string $id,
        string $name,
        string $reference,
        \DateTime $createdAt
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->reference = $reference;
        $this->createdAt = $createdAt;
    }
}