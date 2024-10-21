<?php

declare(strict_types=1);

namespace Specification\Akeneo\Platform\Bundle\CatalogVolumeMonitoringBundle\Persistence\Query\Sql;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use PhpSpec\ObjectBehavior;
use Akeneo\Platform\Bundle\CatalogVolumeMonitoringBundle\Persistence\Query\Sql\AverageMaxProductModelValues;
use Akeneo\Platform\Component\CatalogVolumeMonitoring\Volume\Query\AverageMaxQuery;
use Akeneo\Platform\Component\CatalogVolumeMonitoring\Volume\ReadModel\AverageMaxVolumes;
use Prophecy\Argument;

class AverageMaxProductModelValuesSpec extends ObjectBehavior
{
    function let(Connection $connection)
    {
        $this->beConstructedWith($connection, 12);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(AverageMaxProductModelValues::class);
    }

    function it_is_an_average_and_max_query()
    {
        $this->shouldImplement(AverageMaxQuery::class);
    }

    function it_gets_average_and_max_volume(Connection $connection, Result $statement)
    {
        $connection->executeQuery(Argument::type('string'))->willReturn($statement);
        $statement->fetchAssociative()->willReturn(['average' => '4', 'max' => '10']);

        $this->fetch()->shouldBeLike(new AverageMaxVolumes(10, 4, 'average_max_product_model_values'));
    }

    function it_gets_average_and_max_volume_of_an_empty_catalog(Connection $connection, Result $statement)
    {
        $connection->executeQuery(Argument::type('string'))->willReturn($statement);
        $statement->fetchAssociative()->willReturn(['average' => null, 'max' => null]);

        $this->fetch()->shouldBeLike(new AverageMaxVolumes(0, 0, 'average_max_product_model_values'));
    }
}
