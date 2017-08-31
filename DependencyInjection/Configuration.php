<?php
/**
 * This file is part of the Global Trading Technologies Ltd ad-poller-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * (c) fduch <alex.medwedew@gmail.com>
 *
 * Date: 21.08.17
 */

namespace Gtt\Bundle\AdPollerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class for DI
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('ad_poller');

        $rootNode
            ->fixXmlConfig('poller')
            ->children()
                ->arrayNode('pollers')
                    ->info('List of Active Directory pollers to construct')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->normalizeKeys(true)
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('ldap')
                                ->isRequired()
                                ->children()
                                    ->scalarNode('host')->isRequired()->end()
                                    ->scalarNode('domain')->isRequired()->end()
                                    ->scalarNode('dn')->isRequired()->end()
                                    ->scalarNode('username')->isRequired()->end()
                                    ->scalarNode('password')->isRequired()->end()
                                ->end()
                            ->end()
                            ->arrayNode('ldap_search_server_controls')
                                ->info('List of optional ldap server control options to adjust ldap search
                                See http://php.net/manual/en/function.ldap-set-option.php for details')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('oid')->isRequired()->end()
                                        ->scalarNode('value')->end()
                                        ->scalarNode('iscritical')->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('entity_manager')->defaultValue('default')->end()
                            ->scalarNode('entry_filter')
                                ->info('LDAP filter used to fetch entries')
                                ->isRequired()
                            ->end()
                            ->arrayNode('entry_attributes_to_fetch')
                                ->info('List of Active Directory entry attributes that would be fetched. 
                                 Empty array is treated as all the attributes should be processed.')
                                ->defaultValue([])
                                ->performNoDeepMerging()
                                ->prototype('scalar')->end()
                            ->end()
                            ->booleanNode('detect_deleted')
                                ->info('Flag defines whether to fetch deleted entries during incremental poll or not')
                                ->defaultValue(true)
                            ->end()
                            ->arrayNode('sync')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->enumNode('type')
                                        ->isRequired()
                                        ->info('AD sync type. For now only event dispatching syncing is supported')
                                        ->defaultValue('events')
                                        ->values(array('events'))
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
