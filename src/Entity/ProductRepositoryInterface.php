<?php

namespace Entity;

interface ProductRepositoryInterface
{
    public function find(string $id): Product;

    public function save (Product $product): void;
}