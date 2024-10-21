<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Product\API\Command\UserIntent;

use Webmozart\Assert\Assert;

/**
 * @copyright 2022 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class ChangeParent implements ParentUserIntent
{
    public function __construct(private string $parentCode)
    {
        Assert::stringNotEmpty($this->parentCode);
    }

    public function parentCode(): string
    {
        return $this->parentCode;
    }
}
