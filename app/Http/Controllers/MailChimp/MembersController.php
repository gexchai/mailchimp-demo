<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpMember;
use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mailchimp\Mailchimp;

class MembersController extends Controller
{
    /**
     * @var \Mailchimp\Mailchimp
     */
    private $mailChimp;

    /**
     * MemberController constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Mailchimp\Mailchimp $mailchimp
     */
    public function __construct(EntityManagerInterface $entityManager, Mailchimp $mailchimp)
    {
        parent::__construct($entityManager);

        $this->mailChimp = $mailchimp;
    }

    /**
     * Add a user into a list
     *
     * @param \Illuminate\Http\Request $request
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request, string $listId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        //make sure the list exist
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }
        
        // Instantiate entity
        $member = new MailChimpMember($request->all());
        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $list->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
            // Save member into db
            $this->saveEntity($member);

            //First check is the user already subscribed
            $response = $this->mailChimp->get(\sprintf('lists/%s/members/%s', $list->getMailChimpId(), $member->getMailChimpHash()));
            $currentStats = $response->get('status');
            if ($currentStats != 'unsubscribed') {
                return $this->errorResponse([
                    'message' => 'User has been subscribed or has been removed from the list before.'
                ]);
            }
            // Add user to the list
            $response = $this->mailChimp->post(\sprintf('lists/%s/members/', $list->getMailChimpId()), $member->getSubscribeArray("pending"));
            // create the relationship into the table
            $list->addMember($member);
            $this->saveEntity($list);
        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }


    /**
     * Update a user info inside a list.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $memberId
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $memberId, string $listId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        /** @var \App\Database\Entities\MailChimp\MailChimpMember|null $member */
        $member = $this->entityManager->getRepository(MailChimpMember::class)->find($memberId);

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('Member id [%s] not found', $memberId)],
                404
            );
        }

        // Update list properties
        $member->fill($request->all());

        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
            // Update member into database
            $this->saveEntity($member);
            // Update member details into MailChimp
            $this->mailChimp->patch(\sprintf('lists/%s/members/%s', $list->getMailChimpId(), $member->getMailChimpHash()), $member->toMailChimpArray());
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * Unsubscribe MailChimp list.
     *
     * @param string $listId
     * @param string $memberId
     * @param string $hash
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unsubscribe(string $listId, string $memberId, string $hash): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        /** @var \App\Database\Entities\MailChimp\MailChimpMember|null $member */
        $member = $this->entityManager->getRepository(MailChimpMember::class)->find($memberId);

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('Member id [%s] not found', $memberId)],
                404
            );
        }

        if ($member->getHash() !== $hash) {
            return $this->errorResponse(
                ['message' => 'Member hash does not match'],
                403
            );
        }

        try {
            // Remove list from database
            $member->removeMemberList($list);
            /**
             * Suggested by mailchimp: http://developer.mailchimp.com/documentation/mailchimp/guides/manage-subscribers-with-the-mailchimp-api/
             * We should unsubscribed the user from a list rather than delete.
             */
            $response = $this->mailChimp->patch(\sprintf('lists/%s/members/%s', $list->getMailChimpId(), $member->getMailChimpHash()), $member->getSubscribeArray("unsubscribed"));
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse([]);
    }
}
