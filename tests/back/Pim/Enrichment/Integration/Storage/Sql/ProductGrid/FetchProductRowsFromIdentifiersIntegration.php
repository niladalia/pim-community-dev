<?php

declare(strict_types=1);

namespace AkeneoTest\Pim\Enrichment\Integration\Storage\Sql\ProductGrid;

use Akeneo\Pim\Enrichment\Bundle\Storage\Sql\ProductGrid\FetchProductRowsFromIdentifiers;
use Akeneo\Pim\Enrichment\Component\Product\Grid\ReadModel\Row;
use Akeneo\Pim\Enrichment\Component\Product\Model\WriteValueCollection;
use Akeneo\Pim\Enrichment\Component\Product\Value\MediaValue;
use Akeneo\Pim\Enrichment\Component\Product\Value\ScalarValue;
use Akeneo\Pim\Enrichment\Product\API\Command\UpsertProductCommand;
use Akeneo\Pim\Enrichment\Product\API\Command\UserIntent\ChangeParent;
use Akeneo\Pim\Enrichment\Product\API\Command\UserIntent\SetSimpleSelectValue;
use Akeneo\Test\Integration\Configuration;
use Akeneo\Test\Integration\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Webmozart\Assert\Assert;

class FetchProductRowsFromIdentifiersIntegration extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_fetch_products_from_identifiers()
    {
        $userId = $this
            ->get('database_connection')
            ->fetchColumn('SELECT id FROM oro_user WHERE username = "admin"', [], 0);

        $fixturesLoader = $this->get('akeneo_integration_tests.loader.product_grid_fixtures_loader');
        $imagePath = $this->getFileInfoKey($this->getFixturePath('akeneo.jpg'));
        [$product1, $product2] = $fixturesLoader->createProductAndProductModels($imagePath)['products'];

        $query = $this->getFetchProductRowsFromIdentifiers();
        $rows = $query(['baz', 'foo'], ['sku', 'a_localizable_image', 'a_scopable_image'], 'ecommerce', 'en_US', $userId);

        $akeneoImage = current($this
            ->get('akeneo_file_storage.repository.file_info')
            ->findAll($this->getFixturePath('akeneo.jpg')));

        $expectedRows = [
            Row::fromProduct(
                'foo',
                'A family A',
                ['[groupB]', '[groupA]'],
                true,
                $product1->getCreated(),
                $product1->getUpdated(),
                'foo',
                MediaValue::value('an_image', $akeneoImage),
                31,
                $product1->getUuid()->toString(),
                'sub_product_model',
                new WriteValueCollection([
                    ScalarValue::value('sku', 'foo'),
                    MediaValue::value('an_image', $akeneoImage)
                ])
            ),
            Row::fromProduct(
                'baz',
                null,
                [],
                true,
                $product2->getCreated(),
                $product2->getUpdated(),
                '[baz]',
                null,
                null,
                $product2->getUuid()->toString(),
                null,
                new WriteValueCollection([
                    ScalarValue::value('sku', 'baz'),
                    MediaValue::localizableValue('a_localizable_image', $akeneoImage, 'en_US'),
                    MediaValue::scopableValue('a_scopable_image', $akeneoImage, 'ecommerce'),
                ])
            ),
        ];

        AssertRows::sameButOrderNotGuaranteed($expectedRows, $rows);
    }

    public function test_it_works_with_empty_product_model()
    {
        $this->createFamily('family', ['a_simple_select']);
        $this->createFamilyVariant('family', 'familyVariant', ['a_simple_select']);
        $this->createProductModel('productModel', 'familyVariant');
        $this->createVariantProduct('productVariant', 'productModel');

        $query = $this->getFetchProductRowsFromIdentifiers();
        $result = $query(['productVariant'], ['a_simple_select'], 'ecommerce', 'en_US');

        Assert::count($result, 1);
        $row = $result[0];

        $expectedAttributesCodes = ['sku', 'a_simple_select'];
        $expectedData = ['productVariant', 'optionA'];

        $skuValue = $row->values()->getValues()[0];
        $simpleSelectValue = $row->values()->getValues()[1];

        \PHPUnit\Framework\Assert::assertEqualsCanonicalizing($expectedAttributesCodes, [
            $skuValue->getAttributeCode(),
            $simpleSelectValue->getAttributeCode()
        ]);
        \PHPUnit\Framework\Assert::assertEqualsCanonicalizing($expectedData, [
            $skuValue->getData(),
            $simpleSelectValue->getData()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfiguration(): Configuration
    {
        return $this->catalog->useTechnicalCatalog();
    }

    private function getFetchProductRowsFromIdentifiers(): FetchProductRowsFromIdentifiers
    {
        return $this->get('akeneo.pim.enrichment.product.grid.query.fetch_product_rows_from_identifiers');
    }

    private function createFamily(string $familyCode, array $attributeCodes): void
    {
        $familyData = [
            'code' => $familyCode,
            'attributes' => array_merge(['sku'], $attributeCodes),
        ];

        $family = $this->get('pim_catalog.factory.family')->create();
        $this->get('pim_catalog.updater.family')->update($family, $familyData);
        $errors = $this->get('validator')->validate($family);
        Assert::same(0, $errors->count());
        $this->get('pim_catalog.saver.family')->save($family);
    }

    private function createFamilyVariant(string $familyCode, string $variantCode, array $attributeCodes): void
    {
        $familyVariant = $this->get('pim_catalog.factory.family_variant')->create();
        $this->get('pim_catalog.updater.family_variant')->update($familyVariant, [
            'code' => $variantCode,
            'family' => $familyCode,
            'variant_attribute_sets' => [
                [
                    'axes' => $attributeCodes,
                    'attributes' => $attributeCodes,
                    'level' => 1,
                ]
            ],
        ]);

        $errors = $this->get('validator')->validate($familyVariant);
        Assert::same(0, $errors->count());
        $this->get('pim_catalog.saver.family_variant')->save($familyVariant);
    }

    private function createProductModel(string $productModelCode, $familyVariantCode): void
    {
        $productModel = $this->get('pim_catalog.factory.product_model')->create();
        $this->get('pim_catalog.updater.product_model')->update($productModel, [
            'code' => $productModelCode,
            'family_variant' => $familyVariantCode,
            'values' => [],
        ]);

        $errors = $this->get('validator')->validate($productModel);
        Assert::same(0, $errors->count());
        $this->get('pim_catalog.saver.product_model')->save($productModel);
    }

    private function createVariantProduct(string $identifier, string $parentCode): void
    {
        $this->logIn('admin');
        $command = UpsertProductCommand::createFromCollection(
            userId: $this->getUserId('admin'),
            productIdentifier: $identifier,
            userIntents: [
                new ChangeParent($parentCode),
                new SetSimpleSelectValue('a_simple_select', null, null, 'optionA')
            ]
        );
        $this->get('pim_enrich.product.message_bus')->dispatch($command);
    }

    protected function getUserId(string $username): int
    {
        $query = <<<SQL
            SELECT id FROM oro_user WHERE username = :username
        SQL;
        $stmt = $this->get('database_connection')->executeQuery($query, ['username' => $username]);
        $id = $stmt->fetchOne();
        if (null === $id) {
            throw new \InvalidArgumentException(\sprintf('No user exists with username "%s"', $username));
        }

        return \intval($id);
    }

    protected function logIn(string $username): void
    {
        $session = $this->get('session');
        $user = $this->get('pim_user.repository.user')->findOneByIdentifier($username);
        \PHPUnit\Framework\Assert::assertNotNull($user);

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);

        $session->set('_security_main', serialize($token));
        $session->save();
    }
}

