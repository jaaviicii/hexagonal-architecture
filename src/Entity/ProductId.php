<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;

class ProductId
{
    private $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function generate(): ProductId
    {
        return new self(Uuid::uuid4()->toString());
    }

    public function build(string $id): ProductId
    {
        if (Uuid::isValid($id)) {
            return new self($id);
        } else {
            throw new InvalidIdFormatException("Invalid ProductId format: ".$id);
        }
    }

    public function value(): string
    {
        return $this->id;
    }
}