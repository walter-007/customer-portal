<?php

namespace Oro\Bundle\FrontendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configures frontend API processors.
 */
class FrontendApiPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->disableApiProcessor($container, 'oro_api.collect_resources.load_dictionaries');
        $this->disableApiProcessor($container, 'oro_api.collect_resources.load_custom_entities');
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $processorServiceId
     */
    private function disableApiProcessor(ContainerBuilder $container, string $processorServiceId)
    {
        $processorDef = $container->getDefinition($processorServiceId);
        $tags = $processorDef->getTag('oro.api.processor');
        $processorDef->clearTag('oro.api.processor');

        foreach ($tags as $tag) {
            if (empty($tag['requestType'])) {
                $tag['requestType'] = '!frontend';
            } else {
                $tag['requestType'] .= '&!frontend';
            }
            $processorDef->addTag('oro.api.processor', $tag);
        }
    }
}
