<?php

namespace Akeneo\Pim\Enrichment\Component\Comment\UseCase\Delete;

use Akeneo\Pim\Enrichment\Component\Comment\UseCase\Find\CommentFinder;
use Akeneo\Tool\Component\StorageUtils\Remover\RemoverInterface;
use Akeneo\UserManagement\Component\Model\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DeleteComment
{
    public function __construct(private RemoverInterface $remover, private CommentFinder $finder)
    {
    }

    public function __invoke(string $id, UserInterface $user)
    {
        $comment = $this->finder->__invoke($id);

        if ($comment->getAuthor() !== $user) {
            throw new AccessDeniedException('You are not allowed to delete this comment.');
        }

        $this->remover->remove($comment);
    }
}
