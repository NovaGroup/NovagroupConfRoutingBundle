<?php

namespace Novagroup\ConfRoutingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;


class Configuration implements ConfigurationInterface {
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('novagroup_conf_routing');

        $rootNode
            ->children()
                ->variableNode('bundle')->defaultValue('')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
