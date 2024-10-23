<?php

namespace Akeneo\Pim\Enrichment\Component\Comment\UseCase\Create;

use Akeneo\Pim\Enrichment\Component\Comment\Builder\CommentBuilder;
use Akeneo\Pim\Enrichment\Component\Comment\Model\CommentInterface;
use Akeneo\Pim\Enrichment\Component\Comment\UseCase\Find\CommentFinder;
use Akeneo\Pim\Enrichment\Component\Product\UseCase\Find\ProductFinder;
use Akeneo\Tool\Component\StorageUtils\Saver\SaverInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CommentCreator
{
    public function __construct(
        private ProductFinder $productFinder,
        private CommentFinder  $commentFinder,
        private CommentBuilder $commentBuilder,
        private SaverInterface $commentSaver
    )
    {
    }
    public function __invoke(
        string $body,
        string $aggregate_type,
        string $aggregate_id,
        UserInterface $user,
        ?string $parentId = null,

    ): CommentInterface
    {
        if($aggregate_type == 'product')
            $aggregate = $this->productFinder->__invoke($aggregate_id);

        if($parentId !== null)
            $parentComment = $this->commentFinder->__invoke($parentId);

        $reply = $this->commentBuilder->buildComment($body, $aggregate, $user, $parentComment);

        $this->commentSaver->save($reply);

        if($parentId !== null){
            $parent = $reply->getParent();
            $parent->setRepliedAt( new \DateTime());
            $this->commentSaver->save($parent);
        }

        return $reply;
    }
}
