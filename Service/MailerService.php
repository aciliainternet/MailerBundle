<?php
namespace Acilia\Bundle\MailerBundle\Service;

use Acilia\Bundle\MailerBundle\Library\Providers\Interfaces\DefaultProviderInterface as ProviderInterface;

class MailerService
{
    protected $providers;

    public function __construct()
    {
        $this->providers = array();
    }

    public function getProvider($provider)
    {
        if (isset($this->providers[$provider])) {
            return $this->providers[$provider];
        }
        return null;
    }

    public function addProvider(ProviderInterface $provider)
    {
        $this->providers[$provider->getName()] = $provider;
    }
}
