<?php
declare(strict_types=1);

namespace Tests\App\TestCases\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpMember;
use Illuminate\Http\JsonResponse;
use Mailchimp\Mailchimp;
use Mockery;
use Mockery\MockInterface;
use Tests\App\TestCases\WithDatabaseTestCase;

abstract class MemberTestCase extends WithDatabaseTestCase
{
    protected const MAILCHIMP_EXCEPTION_MESSAGE = 'MailChimp exception';
    
    /**
     * @var string
     */
    protected $listId = '';

    /**
     * @var array
     */
    protected $createdMemberIds = [];

    /**
     * @var array
     */
    protected static $memberData = [
        'list_id' => '45a0563e0b', //my existing list id
        'email_address' => 'gexchai@gmail.com',
        'status' => 'subscribed',
        'merge_fields' => [
            'FNAME'=> 'Gex',
            'LNAME'=> 'Cheng'
        ]
    ];

    /**
     * @var array
     */
    protected static $notRequired = [
        
    ];

    /**
     * Call MailChimp to delete members created during test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        /** @var Mailchimp $mailChimp */
        $mailChimp = $this->app->make(Mailchimp::class);

        foreach ($this->createdMemberIds as $id) {
            // Delete list on MailChimp after test
            $mailChimp->delete(\sprintf('lists/%s', $this->listId).\sprintf('%s', '/members'), $id);
        }

        parent::tearDown();
    }

    /**
     * Asserts error response when member not found.
     *
     * @param string $memberId
     *
     * @return void
     */
    protected function assertMemberNotFoundResponse(string $memberId): void
    {
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals(\sprintf('MailChimpMember[%s] not found', $memberId), $content['message']);
    }

    /**
     * Asserts error response when MailChimp exception is thrown.
     *
     * @param \Illuminate\Http\JsonResponse $response
     *
     * @return void
     */
    protected function assertMailChimpExceptionResponse(JsonResponse $response): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals(400, $response->getStatusCode());
        self::assertArrayHasKey('message', $content);
        self::assertEquals(self::MAILCHIMP_EXCEPTION_MESSAGE, $content['message']);
    }

    /**
     * Create MailChimp member into database.
     *
     * @param array $data
     *
     * @return \App\Database\Entities\MailChimp\MailChimpMember
     */
    protected function createMember(array $data): MailChimpMember
    {   
        $member = new MailChimpMember($data);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * Link Member to a list.
     *
     * @param integer memberId
     * @param string listid
     *
     * @return \App\Database\Entities\MailChimp\MailChimpMember
     */
    protected function linkMemberList(int $memberId, string $listId): MailChimpMember
    {   
        $member = MailChimpMember::find($memberId);
        $list = MailChimpList::find($listid);
        $member->addMemberList($list);
        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * Returns mock of MailChimp to trow exception when requesting their API.
     *
     * @param string $method
     *
     * @return \Mockery\MockInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess) Mockery requires static access to mock()
     */
    protected function mockMailChimpForException(string $method): MockInterface
    {
        $mailChimp = Mockery::mock(Mailchimp::class);

        $mailChimp
            ->shouldReceive($method)
            ->once()
            ->withArgs(function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andThrow(new \Exception(self::MAILCHIMP_EXCEPTION_MESSAGE));

        return $mailChimp;
    }

    
}