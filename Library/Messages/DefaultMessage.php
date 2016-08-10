<?php
namespace Acilia\Bundle\MailerBundle\Library\Messages;

use Acilia\Bundle\MailerBundle\Library\Messages\Interfaces\DefaultMessageInterface;
use Acilia\Bundle\MailerBundle\Library\Messages\Traits\DefaultMessageTrait;

class DefaultMessage implements DefaultMessageInterface
{
    protected $from;
    protected $to;
    protected $cc;
    protected $cco;
    protected $subject;
    protected $body;

    use DefaultMessageTrait;

    public function __construct()
    {
        $this->options = array();
    }

    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function setCC($cc)
    {
        $this->$cc = $cc;
    }

    public function getCC()
    {
        return $this->cc;
    }
    public function setCCO($cco)
    {
        $this->cco=$cco;
    }

    public function getCCO()
    {
        return $this->cco;
    }

    public function setSubject($subject)
    {
        $this->subject=$subject;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setBody($body)
    {
        $this->body=$body;
    }

    public function getBody()
    {
        return $this->body;
    }
}
