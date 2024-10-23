<?php

namespace Akeneo\Pim\Enrichment\Component\Comment\UseCase\Find;

use Akeneo\Pim\Enrichment\Component\Comment\Repository\CommentRepositoryInterface;
use Ramsey\Uuid\Uuid;

class CommentsFinderByEntityRelation
{
    public function __construct(private CommentRepositoryInterface $commentRepository )
    {
    }

    public function __invoke(string $relation, string $uuid): array
    {
        return $this->commentRepository->getCommentsByUuid(
            $relation,
            Uuid::fromString($uuid)
        );
    }
}
