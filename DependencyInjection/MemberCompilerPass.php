<?php
namespace Acilia\Bundle\MailerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class MemberCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('acilia.mailer.member.factory')) {
            return;
        }

        $factory = $container->findDefinition('acilia.mailer.member.factory');
        $providers = $container->findTaggedServiceIds('acilia.mailer.member');

        foreach ($providers as $providerId => $providerTags) {
            $factory->addMethodCall('addMember', array(new Reference($providerId)));
        }
    }
}
