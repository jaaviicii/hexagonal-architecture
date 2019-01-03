<?php

namespace tests\Entity;

use App\Application\DTOs\CreateProductRequestDto;
use App\Application\DTOs\CreateProductResponseDto;
use DateTime;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ProductTest extends TestCase
{
    public function testProductDomainEntity()
    {
        $productId = Uuid::uuid4();
        $createdAt = new DateTime();
        $createRequestDto = new CreateProductRequestDto($productId,'nameTest', 'reference-124', $createdAt);
        $product = Product::fromDto($createRequestDto);
        $result = $product->toDto();

        $expected = new CreateProductResponseDto($productId->toString(), 'nameTest', 'reference-124', $createdAt);

        self::assertEquals($expected, $result);
    }
}
