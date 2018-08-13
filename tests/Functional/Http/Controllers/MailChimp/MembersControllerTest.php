<?php
declare(strict_types=1);

namespace Tests\App\Functional\Http\Controllers\MailChimp;

use Tests\App\TestCases\MailChimp\MemberTestCase;

class MembersControllerTest extends MemberTestCase
{
    /**
     * Test application creates successfully member and returns it back with id from MailChimp.
     *
     * @return void
     */
    public function testCreateMemberSuccessfully(): void
    {
        $this->post(\sprintf('lists/%s/members/', '45a0563e0b'), static::$listData);

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseOk();
        foreach (\array_keys(static::$memberData) as $key) {
            self::assertArrayHasKey($key, $content);
        }
        self::assertNotNull($content['member_id']);
        $this->createdMemberIds[] = $content['member_id'];
    }

    /**
     * Test application returns error response with errors when member validation fails.
     *
     * @return void
     */
    public function testCreateMemberValidationFailed(): void
    {
        $postData = static::$memberData;
        $postData['list_id'] = 'srffdfg';
        $this->post('/mailchimp/members', $postData);

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(400);
        self::assertArrayHasKey('message', $content);
        self::assertArrayHasKey('errors', $content);
       
    }
}
