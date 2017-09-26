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
                            ->arrayNode('entry_filter')
                                ->info('LDAP filter used to fetch entries')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function ($v) { return ['full_sync' => $v, 'incremental_sync' => $v, 'deleted' => $v]; })
                                ->end()
                                ->children()
                                    ->scalarNode('full_sync')
                                        ->info('LDAP filter used to fetch entries during full sync')
                                    ->end()
                                    ->scalarNode('incremental_sync')
                                        ->info('LDAP filter used to fetch entries during incremental sync')
                                    ->end()
                                    ->scalarNode('deleted_sync')
                                        ->info('LDAP filter used to fetch entries during deleted sync (requires detect_deleted to be enabled)')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('entry_attributes_to_fetch')
                                ->info('List of Active Directory entry attributes that would be fetched. 
                                 Empty array is treated as all the attributes should be processed')
                                ->defaultValue([])
                                ->performNoDeepMerging()
                                ->prototype('scalar')->end()
                            ->end()
                            ->booleanNode('detect_deleted')
                                ->info('Flag defines whether to fetch deleted entries during incremental poll or not')
                                ->defaultValue(false)
                            ->end()
                            ->arrayNode('logging')
                                ->canBeDisabled()
                                ->children()
                                    ->arrayNode('incremental_entry_attributes_to_log')
                                        ->info('List of entry attributes to be logged during incremental sync')
                                        ->defaultValue(['dn'])
                                        ->cannotBeEmpty()
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('sync')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->enumNode('type')
                                        ->isRequired()
                                        ->info('AD sync type. For now only event dispatching syncing is supported')
                                        ->defaultValue('events')
                                        // This hack is used describe type with enumNode
                                        // Since 2.7 do not support enum nodes with one choice
                                        // TODO remove this after bumping to 2.8+
                                        ->values(array('events', 'noop'))
                                        ->validate()
                                            ->ifNotInArray(array('events'))
                                            ->thenInvalid('Invalid sync type')
                                        ->end()
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
