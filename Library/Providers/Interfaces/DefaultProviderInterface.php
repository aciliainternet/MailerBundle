<?php
namespace Acilia\Bundle\MailerBundle\Library\Providers\Interfaces;

interface DefaultProviderInterface
{
    public function newMessage();

    public function send($message);

    public function getName();
}
