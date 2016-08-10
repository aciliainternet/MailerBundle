<?php
namespace Acilia\Bundle\MailerBundle\Library\Providers;

use Acilia\Bundle\MailerBundle\Library\Exceptions\IncorrectMessageTypeException;
use Acilia\Bundle\MailerBundle\Library\Providers\Interfaces\DefaultProviderInterface;
use Swift_Message as Message;
use Swift_Mailer;

class SwiftMailerProvider implements DefaultProviderInterface
{
    protected $generalMailerService;

    public function __construct(Swift_Mailer $generalMailerService)
    {
        $this->mailerService = $generalMailerService;
    }

    public function newMessage()
    {
        return Message::newInstance();
    }

    public function send($message)
    {
        if (! $message instanceof Message) {
            throw new IncorrectMessageTypeException(sprintf("Message must be of type %s", Message::class));
        }
        return $this->mailerService->send($message);
    }

    public function getName()
    {
        return 'swift_mailer';
    }
}
