<?php
namespace Acilia\Bundle\MailerBundle\Library\Providers;

use Acilia\Bundle\MailerBundle\Library\Providers\Interfaces\DefaultProviderInterface;
use Acilia\Bundle\MailerBundle\Library\Messages\DefaultMessage as Message;
use Acilia\Bundle\MailerBundle\Library\Exceptions\IncorrectMessageTypeException;

class SmartFocusProvider implements DefaultProviderInterface
{
    public function newMessage()
    {
        return new Message();
    }

    public function send($message)
    {
        if (! $message instanceof Message) {
            throw new IncorrectMessageTypeException(sprintf("Message must be of type %s", Message::class));
        }

        $messageType = $message->getOption('message_type');

        $ch = curl_init();

        switch ($messageType) {
            case 'xml' :
                curl_setopt($ch, CURLOPT_URL, "http://api.notificationmessaging.com/NMSXML");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $message->getBody());
                break;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);

        $output = curl_exec($ch);

        curl_close($ch);

        return $output;
    }

    public function getName()
    {
        return 'smart_focus';
    }
}
