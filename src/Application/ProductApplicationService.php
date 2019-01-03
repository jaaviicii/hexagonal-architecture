<?php

namespace App\Application;

use App\Application\DTOs\CreateProductRequestDto;
use App\Application\DTOs\CreateProductResponseDto;
use Entity\Product;
use Entity\ProductRepositoryInterface;

class ProductApplicationService
{
    private $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function createProduct(CreateProductRequestDto $createProductRequestDto): CreateProductResponseDto
    {
        $product = Product::fromDto($createProductRequestDto);

        $this->productRepository->save($product);

        return $product->toDto();
    }
}