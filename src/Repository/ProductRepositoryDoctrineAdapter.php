<?php

namespace App\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Entity\Product;
use Entity\ProductRepositoryInterface;

class ProductRepositoryDoctrineAdapter implements ProductRepositoryInterface
{
    /** @var EntityRepository */
    private $productRepository;

    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->productRepository = $entityManager->getRepository(Product::class);;
    }

    public function find(string $id): Product
    {
        $this->productRepository->find($id);
    }

    public function save(Product $product): void
    {
        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }
}