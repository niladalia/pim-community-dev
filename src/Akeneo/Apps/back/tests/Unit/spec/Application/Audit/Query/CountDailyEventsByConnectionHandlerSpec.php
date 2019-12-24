<?php

declare(strict_types=1);

namespace spec\Akeneo\Apps\Application\Audit\Query;

use Akeneo\Apps\Application\Audit\Query\CountDailyEventsByConnectionHandler;
use Akeneo\Apps\Application\Audit\Query\CountDailyEventsByConnectionQuery;
use Akeneo\Apps\Domain\Audit\Model\Read\WeeklyEventCounts;
use Akeneo\Apps\Domain\Audit\Model\Read\DailyEventCount;
use Akeneo\Apps\Domain\Audit\Persistence\Query\SelectConnectionsEventCountByDayQuery;
use PhpSpec\ObjectBehavior;

/**
 * @author Romain Monceau <romain@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class CountDailyEventsByConnectionHandlerSpec extends ObjectBehavior
{
    function let(SelectConnectionsEventCountByDayQuery $selectConnectionsEventCountByDayQuery)
    {
        $this->beConstructedWith($selectConnectionsEventCountByDayQuery);
    }

    function it_is_initializable()
    {
        $this->shouldBeAnInstanceOf(CountDailyEventsByConnectionHandler::class);
    }

    function it_handles_the_event_count($selectConnectionsEventCountByDayQuery)
    {
        $eventCountByConnection1 = new WeeklyEventCounts('Magento');
        $eventCountByConnection1->addDailyEventCount(new DailyEventCount(42, new \DateTime('2019-12-10')));
        $eventCountByConnection1->addDailyEventCount(new DailyEventCount(123, new \DateTime('2019-12-11')));

        $eventCountByConnection2 = new WeeklyEventCounts('Bynder');
        $eventCountByConnection2->addDailyEventCount(new DailyEventCount(36, new \DateTime('2019-12-11')));

        $selectConnectionsEventCountByDayQuery
            ->execute('product_created', '2019-12-10', '2019-12-12')
            ->willReturn([$eventCountByConnection1, $eventCountByConnection2]);

        $query = new CountDailyEventsByConnectionQuery('product_created', '2019-12-10', '2019-12-12');
        $this->handle($query)->shouldReturn([$eventCountByConnection1, $eventCountByConnection2]);
    }
}
