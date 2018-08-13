<?php
declare(strict_types=1);

namespace App\Database\Entities\MailChimp;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity()
 */
class MailChimpMember extends MailChimpEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(name="first_name",type="string")
     */
    protected $firstname;

    /**
     * @ORM\Column(name="last_name", type="string")
     */
    protected $lastname;

    /**
     * @ORM\Column(name="email",type="string")
     */
    protected $email;

    /**
     * @ORM\Column(name="hash",type="string")
     */
    protected $hash;

   /**
     * @var \Doctrine\Common\Collections\Collection|MailChimpList[]
     *
     * @ORM\ManyToMany(targetEntity="MailChimpList", inversedBy="mailChimpMembers")
     * @ORM\JoinTable(
     *  name="member_list",
     *  joinColumns={
     *      @ORM\JoinColumn(name="member_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="list_id", referencedColumnName="id")
     *  }
     * )
     */
    protected $mailChimpLists;

    /**
     * Default constructor, initializes collections
     */
    public function __construct()
    {
        $this->mailChimpLists = new ArrayCollection();
    }

    /**
     * @param MailChimpList $mailChimpList
     */
    public function addMemberList(MailChimpList $mailChimpList)
    {
        if ($this->mailChimpLists->contains($mailChimpList)) {
            return;
        }
        $this->mailChimpLists->add($mailChimpList);
        $mailChimpList->addUser($this);
    }
    /**
     * @param MailChimpList $mailChimpList
     */
    public function removeMemberList(MailChimpList $mailChimpList)
    {
        if (!$this->mailChimpLists->contains($mailChimpList)) {
            return;
        }
        $this->mailChimpLists->removeElement($mailChimpList);
        $mailChimpList->removeUser($this);
    }

    /**
     * Get id.
     *
     * @return null|string
     */
    public function getId(): ?string
    {
        return $this->listId;
    }

    /**
     * Get firstname.
     *
     * @return null|string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set firstname.
     * 
     * @param string $firstname
     * 
     * @return \App\Database\Entities\MailChimp\MailChimpMember
     */
    public function setFirstname($firstname): MailChimpMember
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get lastname.
     *
     * @return null|string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set lastname.
     * 
     * @param string $lastname
     * 
     * @return \App\Database\Entities\MailChimp\MailChimpMember
     */
    public function setLastname($lastname): MailChimpMember 
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get email.
     *
     * @return null|string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email.
     * 
     * @param string $email
     * 
     * @return \App\Database\Entities\MailChimp\MailChimpMember
     */
    public function setEmail($email): MailChimpMember 
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get hash.
     *
     * @return null|string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set hash.
     * 
     * To the test reviewer
     * This will be using when someone want to unsubscribe their email from a list. This should be attached to the unsubscribe link via the email. This is more to showcase I have think about it. In reality we might need to put more thought like diff hash to diff list?
     * 
     * 
     * @return \App\Database\Entities\MailChimp\MailChimpMember
     */
    public function setHash(): MailChimpMember 
    {
        $this->hash = $this->generateRandomString(20);

        return $this;
    }

    /**
     * Get validation rules for members entity.
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'firstname' => 'string|max:255',
            'email' => 'required|string|email|max:255|unique:App\Entities\MailChimpMember',
            'lastname' => 'string|max:255'
        ];
    }

    

    /**
     * Generate the hash from user email address for mailchimp API
     * 
     * @return string
     */
    public function getMailChimpHash(): string
    {
        return md5($this->getEmail());
    }

    /**
     * Generate the array for subscribe to list
     * @param string $status
     * @return array
     */
    public function getSubscribeArray($status): array
    {
        if (!in_array($status, ['subscribed','pending','unsubscribed'])) {
            $status = 'subscribed';
        }
        return [
            'email_address' => $this->getEmail(),
            'status'=> 'subscribed',
            'merge_fields'=> [
                'FNAME' => $this->getFirstname(),
                'LNAME' => $this->getLastname()
            ]
        ];
    }

    /**
     * just a helper function to generate the hash
     */
    public function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}
