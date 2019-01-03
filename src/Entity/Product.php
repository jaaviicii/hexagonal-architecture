<?php

namespace App\Entity;

use App\Application\DTOs\CreateProductRequestDto;
use App\Application\DTOs\CreateProductResponseDto;
use DateTime;
use Ramsey\Uuid\Uuid;

class Product
{
    private $id;

    private $name;

    private $reference;

    private $createdAt;

    private function __construct(
        Uuid $id,
        string $name,
        string $reference,
        DateTime $createdAt
    )
    {
        $this->id = $id->toString();
        $this->name = $name;
        $this->reference = $reference;
        $this->createdAt =$createdAt;
    }

    public static function fromDto(CreateProductRequestDto $createProductResponseDto): Product
    {
        return new Product(
            $createProductResponseDto->id(),
            $createProductResponseDto->name(),
            $createProductResponseDto->reference(),
            $createProductResponseDto->createdAt()
        );
    }

    public function toDto()
    {
        return new CreateProductResponseDto($this->id, $this->name, $this->reference, $this->createdAt);
    }
}