<?php
namespace Acilia\Bundle\MailerBundle\Library\Messages\Traits;

trait DefaultMessageTrait
{
    protected $options;

    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    public function getOption($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }
        return false;
    }
}
