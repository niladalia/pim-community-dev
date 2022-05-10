<?php

declare(strict_types=1);

use Akeneo\CouplingDetector\Configuration\Configuration;
use Akeneo\CouplingDetector\Configuration\DefaultFinder;
use Akeneo\CouplingDetector\RuleBuilder;

$finder = new DefaultFinder();
$finder->notPath('tests');
$builder = new RuleBuilder();

$rules = [
    // Domain layer should only use classes from Domain
    $builder->only(
        [
            'Akeneo\Catalogs\Domain',

            // Constraints attributes
            'Symfony\Component\Validator\Constraints',
        ]
    )->in('Akeneo\Catalogs\Domain'),

    // Application layer should only use classes from Domain or Application
    $builder->only(
        [
            'Akeneo\Catalogs\Domain',
            'Akeneo\Catalogs\Application',

            // Dispatch events
            'Psr\EventDispatcher\EventDispatcherInterface',
        ]
    )->in('Akeneo\Catalogs\Application'),

    // Infrastructure layer can use anything, but we track used dependencies anyway to detect changes
    $builder->only(
        [
            'Akeneo\Catalogs\Domain',
            'Akeneo\Catalogs\Application',
            'Akeneo\Catalogs\Infrastructure',

            'Symfony\Component',
            'Doctrine\DBAL',
            'Ramsey\Uuid\Uuid',
            'Akeneo\Platform',
            'Akeneo\Tool\Component\Api',
            'Akeneo\UserManagement\Component\Model\UserInterface',

            // @todo remove
            'Akeneo\Connectivity\Connection\Infrastructure\Apps\Security\ScopeMapperInterface',
        ]
    )->in('Akeneo\Catalogs\Infrastructure'),
];

$config = new Configuration($rules, $finder);

return $config;
