<?php

namespace Entity;

use App\Entity\Product;

interface ProductRepositoryInterface
{
    public function find(string $id): Product;

    public function save (Product $product): void;
}