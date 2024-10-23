<?php

namespace Akeneo\Pim\Enrichment\Component\Comment\UseCase\Find;

use Akeneo\Pim\Enrichment\Component\Comment\Model\CommentInterface;
use Akeneo\Pim\Enrichment\Component\Comment\Repository\CommentRepositoryInterface;

class CommentFinder
{
    public function __construct(private CommentRepositoryInterface $commentRepository )
    {
    }

    public function __invoke(string $uuid): CommentInterface
    {
        return $this->commentRepository->find($uuid);
    }
}
