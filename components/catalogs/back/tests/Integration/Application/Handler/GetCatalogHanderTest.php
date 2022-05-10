<?php

declare(strict_types=1);

namespace Akeneo\Catalogs\Test\Integration\Application\Handler;

use Akeneo\Catalogs\Domain\Query\GetCatalogQuery;
use Akeneo\Catalogs\Test\Integration\IntegrationTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @copyright 2022 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class GetCatalogHanderTest extends IntegrationTestCase
{
    private ?ValidatorInterface $validator;

    public function setUp(): void
    {
        parent::setUp();

        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    /**
     * @dataProvider validations
     */
    public function testItValidatesTheQuery(GetCatalogQuery $query, string $error): void
    {
        $violations = $this->validator->validate($query);

        $this->assertViolationsListContains($violations, $error);
    }

    public function validations(): array
    {
        return [
            'id is not empty' => [
                'query' => new GetCatalogQuery(
                    id: '',
                ),
                'error' => 'This value should not be blank.',
            ],
            'id is an uuid' => [
                'query' => new GetCatalogQuery(
                    id: 'not an uuid',
                ),
                'error' => 'This is not a valid UUID.',
            ],
        ];
    }
}
