<?php

declare(strict_types=1);

namespace Akeneo\Apps\Domain\Settings\Persistence\Query;

use Akeneo\Apps\Domain\Settings\Model\Read\ConnectionWithCredentials;

/**
 * @author Romain Monceau <romain@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
interface SelectConnectionWithCredentialsByCodeQuery
{
    public function execute(string $code): ?ConnectionWithCredentials;
}
