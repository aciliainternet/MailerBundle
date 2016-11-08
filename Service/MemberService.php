<?php

namespace Acilia\Bundle\MailerBundle\Service;

use Acilia\Bundle\MailerBundle\Library\Members\Interfaces\DefaultMemberInterface as MemberInterface;

class MemberService
{
    protected $members;

    public function __construct()
    {
        $this->members = [];
    }

    public function getMember($member)
    {
        if (isset($this->members[$member])) {
            return $this->members[$member];
        }
        return null;
    }

    public function addMember(MemberInterface $member)
    {
        $this->members[$member->getName()] = $member;
    }
}
