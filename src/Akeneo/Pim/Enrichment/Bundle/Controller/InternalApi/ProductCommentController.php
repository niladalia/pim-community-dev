<?php

namespace Akeneo\Pim\Enrichment\Bundle\Controller\InternalApi;

use Akeneo\Pim\Enrichment\Component\Comment\UseCase\Create\CommentCreator;
use Akeneo\Pim\Enrichment\Component\Comment\UseCase\Delete\DeleteComment;
use Akeneo\Pim\Enrichment\Component\Comment\UseCase\Find\CommentsFinderByEntityRelation;
use Akeneo\Pim\Enrichment\Component\Product\Model\Product;
use Akeneo\Platform\Bundle\UIBundle\Resolver\LocaleResolver;
use Akeneo\Tool\Component\Localization\Presenter\PresenterInterface;
use Akeneo\UserManagement\Bundle\Context\UserContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Controller for product comments
 *
 * @author    Filips Alpe <filips@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductCommentController extends InternalApiController
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly NormalizerInterface $normalizer,
        private readonly PresenterInterface $datetimePresenter,
        private readonly LocaleResolver $localeResolver,
        private readonly UserContext $userContext,
        private readonly CommentsFinderByEntityRelation $commentsFinder,
        private readonly CommentCreator $creator,
        private readonly DeleteComment $remover
    ) {
        parent::__construct();
    }

    /**
     * List comments made on a product
     *
     * @param string $uuid
     *
     * @AclAncestor("pim_enrich_product_comment")
     *
     * @return JsonResponse
     */
    public function getAction($uuid)
    {
        $commentsResult = $this->commentsFinder->__invoke(Product::class, $uuid);

        $comments = $this->normalizer->normalize($commentsResult, 'standard');

        foreach ($comments as $commentKey => $comment) {
            $comments[$commentKey]['created'] = $this->presentDate($comment['created']);
            $comments[$commentKey]['replied'] = $this->presentDate($comment['replied']);

            foreach ($comment['replies'] as $replyKey => $reply) {
                $comments[$commentKey]['replies'][$replyKey]['created'] = $this->presentDate($reply['created']);
                $comments[$commentKey]['replies'][$replyKey]['replied'] = $this->presentDate($reply['created']);
            }
        }

        return new JsonResponse($comments);
    }

    /**
     * Create a comment on a product
     */
    public function postAction(Request $request, string $uuid): Response
    {
        $this->isXmlHttpRequest($request);

        $data = json_decode($request->getContent(), true);

        $comment = $this->creator->__invoke($data['body'], 'product', $uuid, $this->getUser());

        return new JsonResponse($this->normalizer->normalize($comment, 'standard'));

    }

    /**
     * Reply to a product comment
     *
     * @param string $commentId
     */
    public function postReplyAction(Request $request, string $uuid, $commentId): Response
    {
        $this->isXmlHttpRequest($request);

        $data = json_decode($request->getContent(), true);

        $comment = $this->creator->__invoke($data['body'], 'product', $uuid, $this->getUser(), $commentId);

        return new JsonResponse($this->normalizer->normalize($comment, 'standard'));
    }

    public function deleteAction(Request $request, $id)
    {
        $this->isXmlHttpRequest($request);

        $this->remover->__invoke($id, $this->getUser());

        return new JsonResponse();
    }

    /**
     * Get the user from the Security Context
     *
     * @return UserInterface|null
     */
    protected function getUser()
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    /**
     * @param string $date
     *
     * @return string
     */
    protected function presentDate($date)
    {
        $context = [
            'locale' => $this->localeResolver->getCurrentLocale(),
            'timezone' => $this->userContext->getUserTimezone(),
        ];
        $dateTime = new \DateTime($date);

        return $this->datetimePresenter->present($dateTime, $context);
    }
}
