<?php
namespace Acilia\Bundle\MailerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class LoaderCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('acilia.mailer.factory')) {
            return;
        }

        $factory = $container->findDefinition('acilia.mailer.factory');
        $providers = $container->findTaggedServiceIds('acilia.mailer.provider');

        foreach ($providers as $providerId => $providerTags) {
            $factory->addMethodCall('addProvider', array(new Reference($providerId)));
        }
    }
}
