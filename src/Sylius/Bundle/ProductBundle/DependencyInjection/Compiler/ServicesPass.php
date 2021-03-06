<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\ProductBundle\DependencyInjection\Compiler;

use Sylius\Component\Resource\Factory\Factory;
use Sylius\Component\Translation\Factory\TranslatableFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;

/**
 * @author Magdalena Banasiak <magdalena.banasiak@lakion.com>
 */
class ServicesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $factoryDefinition = new Definition(Factory::class);
        $factoryDefinition->setArguments(
            array(
                new Parameter('sylius.model.product.class')
            )
        );

        $translatableFactoryDefinition = $container->getDefinition('sylius.factory.product');
        $productFactoryClass = $translatableFactoryDefinition->getClass();
        $translatableFactoryDefinition->setClass(TranslatableFactory::class);
        $translatableFactoryDefinition->setArguments(
            array(
                $factoryDefinition,
                new Reference('sylius.translation.locale_provider')
            )
        );

        $decoratedProductFactoryDefinition = new Definition($productFactoryClass);
        $decoratedProductFactoryDefinition->setArguments(
            array(
                $translatableFactoryDefinition,
                new Reference('sylius.factory.product_variant')
            )
        );

        $container->setDefinition('sylius.factory.product', $decoratedProductFactoryDefinition);
    }
}
