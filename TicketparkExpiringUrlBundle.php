<?php

namespace Ticketpark\ExpiringUrlBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Ticketpark\ExpiringUrlBundle\DependencyInjection\Compiler\SetRouterPass;

class TicketparkExpiringUrlBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SetRouterPass());
    }
}
