<?php

declare(strict_types=1);

namespace Akeneo\Test\Pim\Enrichment\Product\Integration\Handler;

use Akeneo\Pim\Enrichment\Component\Product\EntityWithFamilyVariant\AddParent;
use Akeneo\Pim\Enrichment\Component\Product\Repository\ProductRepositoryInterface;
use Akeneo\Pim\Enrichment\Product\API\Command\Exception\ViolationsException;
use Akeneo\Pim\Enrichment\Product\API\Command\UpsertProductCommand;
use Akeneo\Pim\Enrichment\Product\API\Command\UserIntent\Association\AssociateProducts;
use Akeneo\Pim\Enrichment\Product\API\Command\UserIntent\Association\AssociationUserIntentCollection;
use Akeneo\Pim\Enrichment\Product\API\Command\UserIntent\Association\ReplaceAssociatedProducts;
use Akeneo\Pim\Enrichment\Product\API\Command\UserIntent\ChangeParent;
use Akeneo\Pim\Enrichment\Product\API\Command\UserIntent\RemoveCategories;
use Akeneo\Pim\Enrichment\Product\API\Command\UserIntent\SetCategories;
use Akeneo\Pim\Enrichment\Product\API\Command\UserIntent\SetSimpleSelectValue;
use Akeneo\Pim\Enrichment\Product\API\Command\UserIntent\SetTextValue;
use Akeneo\Test\Pim\Enrichment\Product\Helper\FeatureHelper;
use Akeneo\Test\Pim\Enrichment\Product\Integration\EnrichmentProductTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\Messenger\MessageBusInterface;

final class UpsertProductWithPermissionIntegration extends EnrichmentProductTestCase
{
    private ProductRepositoryInterface $productRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        FeatureHelper::skipIntegrationTestWhenPermissionFeatureIsNotActivated();
        parent::setUp();

        $this->loadEnrichmentProductFunctionalFixtures();

        $this->messageBus = $this->get('pim_enrich.product.message_bus');
        $this->productRepository = $this->get('pim_catalog.repository.product');
    }

    /** @test */
    public function it_throws_an_exception_when_user_category_is_not_granted(): void
    {
        $this->createProduct('identifier', [new SetCategories(['print'])]);

        $product = $this->productRepository->findOneByIdentifier('identifier');
        Assert::assertNotNull($product);
        $this->getContainer()->get('pim_catalog.validator.unique_value_set')->reset(); // Needed to update the product

        $this->expectException(ViolationsException::class);
        $this->expectExceptionMessage('You don\'t have access to products in any tree, please contact your administrator');

        $command = new UpsertProductCommand(userId: $this->getUserId('mary'), productIdentifier: 'identifier', valueUserIntents: [
            new SetTextValue('a_text', null, null, 'foo'),
        ]);
        $this->messageBus->dispatch($command);
    }

    /** @test */
    public function it_throws_an_exception_when_user_locale_is_not_granted(): void
    {
        $this->expectException(ViolationsException::class);
        $this->expectExceptionMessage('You don\'t have access to product data in any activated locale, please contact your administrator');

        $command = new UpsertProductCommand(userId: $this->getUserId('mary'), productIdentifier: 'identifier', valueUserIntents: [
            new SetTextValue('name', null, 'en_GB', 'foo'),
        ]);
        $this->messageBus->dispatch($command);
    }

    /** @test */
    public function it_creates_a_new_uncategorized_product(): void
    {
        $command = new UpsertProductCommand(userId: $this->getUserId('mary'), productIdentifier: 'new_product', valueUserIntents: [
            new SetTextValue('name', null, null, 'foo'),
        ]);
        $this->messageBus->dispatch($command);

        $this->clearDoctrineUoW();
        $product = $this->productRepository->findOneByIdentifier('new_product');
        Assert::assertNotNull($product);
        Assert::assertSame('new_product', $product->getIdentifier());
        Assert::assertNotNull($product->getValue('name'));
    }

    /** @test */
    public function it_creates_a_categorized_product(): void
    {
        $command = new UpsertProductCommand(
            userId: $this->getUserId('betty'),
            productIdentifier: 'identifier',
            categoryUserIntent: new SetCategories(['print'])
        );
        $this->messageBus->dispatch($command);

        $this->clearDoctrineUoW();
        $product = $this->productRepository->findOneByIdentifier('identifier');

        Assert::assertNotNull($product);
        Assert::assertSame('identifier', $product->getIdentifier());
        Assert::assertEqualsCanonicalizing(['print'], $product->getCategoryCodes());
    }

    /** @test */
    public function it_throws_an_exception_when_creating_a_product_with_non_viewable_category(): void
    {
        $this->expectException(ViolationsException::class);
        $this->expectExceptionMessage('The "suppliers" category does not exist');

        $command = new UpsertProductCommand(
            userId: $this->getUserId('betty'),
            productIdentifier: 'identifier',
            categoryUserIntent: new SetCategories(['suppliers'])
        );
        $this->messageBus->dispatch($command);
    }

    /** @test */
    public function it_throws_an_exception_when_updating_a_product_with_non_viewable_category(): void
    {
        $this->createProduct('identifier', [new SetCategories(['print'])]);

        $this->expectException(ViolationsException::class);
        $this->expectExceptionMessage('The "suppliers" category does not exist');

        $command = new UpsertProductCommand(
            userId: $this->getUserId('betty'),
            productIdentifier: 'identifier',
            categoryUserIntent: new SetCategories(['suppliers'])
        );
        $this->messageBus->dispatch($command);
    }

    /** @test */
    public function it_throws_an_exception_when_creating_a_product_without_owned_category(): void
    {
        $this->expectException(ViolationsException::class);
        $this->expectExceptionMessage("You should at least keep your product in one category on which you have an own permission");

        $command = new UpsertProductCommand(
            userId: $this->getUserId('betty'),
            productIdentifier: 'identifier',
            categoryUserIntent: new SetCategories(['sales'])
        );
        $this->messageBus->dispatch($command);
    }

    /** @test */
    public function it_throws_an_exception_when_there_is_no_more_owned_category_after_update(): void
    {
        $this->createProduct('identifier', [new SetCategories(['print'])]);

        $this->expectException(ViolationsException::class);
        $this->expectExceptionMessage('You should at least keep your product in one category on which you have an own permission');

        $command = new UpsertProductCommand(
            userId: $this->getUserId('betty'),
            productIdentifier: 'identifier',
            categoryUserIntent: new SetCategories(['sales']) // betty can view 'sales' category, but is not owner.
        );
        $this->messageBus->dispatch($command);
    }

    /** @test */
    public function it_throws_an_exception_when_there_is_no_more_owned_category_after_removing_category(): void
    {
        $this->createProduct('identifier', [new SetCategories(['print', 'sales'])]);

        $this->expectException(ViolationsException::class);
        $this->expectExceptionMessage('You should at least keep your product in one category on which you have an own permission');

        $command = new UpsertProductCommand(
            userId: $this->getUserId('betty'),
            productIdentifier: 'identifier',
            categoryUserIntent: new RemoveCategories(['print'])
        );
        $this->messageBus->dispatch($command);
    }

    /** @test */
    public function it_throws_an_exception_when_user_is_not_owner(): void
    {
        $this->createProduct('my_product', [new SetCategories(['sales'])]);

        $this->expectException(ViolationsException::class);
        $this->expectExceptionMessage("You don't have access to products in any tree, please contact your administrator");

        $command = new UpsertProductCommand(
            userId: $this->getUserId('betty'),
            productIdentifier: 'my_product',
            categoryUserIntent: new SetCategories(['print'])
        );
        $this->messageBus->dispatch($command);
    }

    /** @test */
    public function it_merges_non_viewable_category_on_update(): void
    {
        $this->createProduct('my_product', [new SetCategories(['print', 'suppliers'])]);

        $command = new UpsertProductCommand(
            userId: $this->getUserId('betty'),
            productIdentifier: 'my_product',
            categoryUserIntent: new SetCategories(['print', 'sales'])
        );
        $this->messageBus->dispatch($command);

        $this->clearDoctrineUoW();
        $product = $this->productRepository->findOneByIdentifier('my_product');

        Assert::assertNotNull($product);
        Assert::assertSame('my_product', $product->getIdentifier());
        Assert::assertEqualsCanonicalizing(['print', 'sales', 'suppliers'], $product->getCategoryCodes());
    }

    /** @test */
    public function it_merges_non_viewable_associated_products_on_replace_products_association(): void
    {
        $this->createProduct('my_other_product', [new SetCategories(['print', 'sales'])]);
        $this->createProduct('my_former_product_2', [new SetCategories(['suppliers'])]);
        $this->createProductModel('root', 'color_variant_accessories', [
            'categories' => ['suppliers'],
        ]);

        $command = new UpsertProductCommand(
            userId: $this->getUserId('peter'),
            productIdentifier: 'my_former_product',
            parentUserIntent: new ChangeParent('root'),
            valueUserIntents: [
                new SetSimpleSelectValue('main_color', null, null, 'green'),
            ]
        );
        $this->messageBus->dispatch($command);

        $this->clearDoctrineUoW();

        $command = new UpsertProductCommand(
            userId: $this->getUserId('peter'),
            productIdentifier: 'my_product',
            associationUserIntent: new AssociationUserIntentCollection([new AssociateProducts('X_SELL', ['my_former_product', 'my_former_product_2'])])
        );
        $this->messageBus->dispatch($command);

        $this->getContainer()->get('pim_catalog.validator.unique_value_set')->reset();
        $this->clearDoctrineUoW();

        $associations = new AssociationUserIntentCollection([new ReplaceAssociatedProducts('X_SELL', ['my_other_product'])]);
        $command = new UpsertProductCommand(
            userId: $this->getUserId('betty'),
            productIdentifier: 'my_product',
            associationUserIntent: $associations
        );
        $this->messageBus->dispatch($command);

        $this->getContainer()->get('pim_catalog.validator.unique_value_set')->reset();
        $this->clearDoctrineUoW();

        $product = $this->productRepository->findOneByIdentifier('my_product');

        Assert::assertNotNull($product);
        Assert::assertSame('my_product', $product->getIdentifier());
        Assert::assertEqualsCanonicalizing(['my_former_product', 'my_former_product_2', 'my_other_product'], $this->getAssociatedProductIdentifiers($product));
    }
}
