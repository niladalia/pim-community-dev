<?php

declare(strict_types=1);

namespace spec\Akeneo\Connectivity\Connection\Application\Webhook\Query;

use Akeneo\Connectivity\Connection\Application\Webhook\Query\GetAConnectionWebhookHandler;
use Akeneo\Connectivity\Connection\Application\Webhook\Query\GetAConnectionWebhookQuery;
use Akeneo\Connectivity\Connection\Domain\Webhook\Model\Read\ConnectionWebhook;
use Akeneo\Connectivity\Connection\Domain\Webhook\Model\Read\EventSubscriptionFormData;
use Akeneo\Connectivity\Connection\Domain\Webhook\Persistence\Query\CountActiveEventSubscriptionsQueryInterface;
use Akeneo\Connectivity\Connection\Domain\Webhook\Persistence\Query\GetAConnectionWebhookQueryInterface;
use PhpSpec\ObjectBehavior;

class GetAConnectionWebhookHandlerSpec extends ObjectBehavior
{
    public const ACTIVE_EVENT_SUBSCRIPTIONS_LIMIT = 3;

    public function let(
        GetAConnectionWebhookQueryInterface $getAConnectionWebhookQuery,
        CountActiveEventSubscriptionsQueryInterface $countActiveEventSubscriptionsQuery
    ): void {
        $this->beConstructedWith(
            $getAConnectionWebhookQuery,
            self::ACTIVE_EVENT_SUBSCRIPTIONS_LIMIT,
            $countActiveEventSubscriptionsQuery
        );
    }

    public function it_is_a_handler(): void
    {
        $this->shouldHaveType(GetAConnectionWebhookHandler::class);
    }

    public function it_gets_a_connection_webhook_given_a_provided_code(
        $getAConnectionWebhookQuery,
        $countActiveEventSubscriptionsQuery
    ): void {
        $eventSubscription = new ConnectionWebhook(
            'magento',
            true,
            '1234_secret',
            'any-url.com'
        );

        $getAConnectionWebhookQuery->execute('magento')->willReturn($eventSubscription);
        $countActiveEventSubscriptionsQuery->execute()->willReturn(2);

        $expectedFormData = new EventSubscriptionFormData(
            $eventSubscription,
            self::ACTIVE_EVENT_SUBSCRIPTIONS_LIMIT,
            2
        );

        $this->handle(new GetAConnectionWebhookQuery('magento'))->shouldBeLike($expectedFormData);
    }

    public function it_returns_null_if_no_connection_webhook_exists($getAConnectionWebhookQuery): void
    {
        $getAConnectionWebhookQuery->execute('magento')->willReturn(null);

        $this->handle(new GetAConnectionWebhookQuery('magento'))->shouldReturn(null);
    }
}
