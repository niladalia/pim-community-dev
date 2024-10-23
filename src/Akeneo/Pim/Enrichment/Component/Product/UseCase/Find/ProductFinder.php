<?php

namespace Akeneo\Pim\Enrichment\Component\Product\UseCase\Find;

use Akeneo\Pim\Enrichment\Component\Product\Repository\ProductRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductFinder
{
    public function __construct(private ProductRepositoryInterface $productRepository )
    {
    }

    public function __invoke(string $uuid)
    {
        $product = $this->productRepository->find($uuid);

        if (!$product) {
            throw new NotFoundHttpException(
                sprintf('Product with uuid %s could not be found.', $uuid)
            );
        }

        return $product;
    }
}
