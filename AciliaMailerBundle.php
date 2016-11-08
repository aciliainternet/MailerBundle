<?php
namespace Acilia\Bundle\MailerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Acilia\Bundle\MailerBundle\DependencyInjection\LoaderCompilerPass;
use Acilia\Bundle\MailerBundle\DependencyInjection\MemberCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AciliaMailerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new LoaderCompilerPass());
        $container->addCompilerPass(new MemberCompilerPass());
    }
}
