<?php
namespace Acilia\Bundle\MailerBundle\Library\Messages\Interfaces;

interface DefaultMessageInterface
{
    public function setFrom($from);

    public function getFrom();

    public function setTo($to);

    public function getTo();

    public function setCC($cc);

    public function getCC();

    public function setCCO($cco);

    public function getCCO();

    public function setSubject($subject);

    public function getSubject();

    public function setBody($body);

    public function getBody();
}
