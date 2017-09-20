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

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Bundle DI extension
 *
 * @author fduch <alex.medwedew@gmail.com>
 */
class AdPollerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($config, $container);
        $config = $this->processConfiguration($configuration, $config);

        if (isset($config['pollers'])) {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
            $loader->load('poller.xml');

            $pollerCollectionDefinition = $container->getDefinition('gtt.ad_poller.poller_collection');
            foreach ($config['pollers'] as $name => $pollerConfig) {
                $ldapDefinitionId         = $this->createLdap($container, $name, $pollerConfig['ldap']);
                $synchronizerDefinitionId = $this->createSynchronizer($container, $name, $pollerConfig['sync']);
                $pollerDefinitionId       = $this->createPoller($container, $name, $ldapDefinitionId, $synchronizerDefinitionId, $pollerConfig);
                $pollerCollectionDefinition->addMethodCall('addPoller', [new Reference($pollerDefinitionId)]);
            }
        }
    }

    /**
     * Creates an LDAP definition
     *
     * @param ContainerBuilder $container container builder
     * @param string           $name      name of ldap connector
     * @param array            $config    config for ldap connector
     *
     * @return string
     */
    private function createLdap(ContainerBuilder $container, $name, array $config)
    {
        $definition = new DefinitionDecorator('gtt.ad_poller.ldap.prototype');
        $definition->setArguments([
            [
                'host'              => $config['host'],
                'username'          => $config['username'] . '@' . $config['domain'],
                'password'          => $config['password'],
                'accountDomainName' => $config['domain'],
                'baseDn'            => $config['dn']
            ]
        ]);

        $id = 'gtt.ad_poller.ldap.' . $name;
        $container->setDefinition($id, $definition);

        return $id;
    }

    /**
     * Creates an synchronizer definition
     *
     * @param ContainerBuilder $container container builder
     * @param string           $name      name of synchronizer
     * @param array            $config    config for synchronizer connector
     *
     * @return string
     */
    private function createSynchronizer(ContainerBuilder $container, $name, array $config)
    {
        $definition = new DefinitionDecorator(sprintf("gtt.ad_poller.synchronizer.%s.prototype", $config['type']));

        switch ($config['type']) {
            case 'events':
                // Nothing to decorate inside the definition here
                break;
            default:
                throw new InvalidConfigurationException(sprintf('Unsupported synchronizator type "%s"', $config['type']));
        }

        $id = sprintf("gtt.ad_poller.synchronizer.%s.%s", $config['type'], $name);
        $container->setDefinition($id, $definition);

        return $id;
    }

    /**
     * Creates poller definition
     *
     * @param ContainerBuilder $container
     * @param string           $name
     * @param string           $ldapDefinitionId
     * @param string           $synchronizerDefinitionId
     * @param array            $pollerConfig
     *
     * @return string
     */
    private function createPoller(ContainerBuilder $container, $name, $ldapDefinitionId, $synchronizerDefinitionId, $pollerConfig)
    {
        $pollerDefinition = new DefinitionDecorator('gtt.ad_poller.poller.prototype');
        $pollerDefinition->setArguments(
            [
                new Reference($synchronizerDefinitionId),
                new Reference($ldapDefinitionId),
                new Reference(sprintf('doctrine.orm.%s_entity_manager', $pollerConfig['entity_manager'])),
                $pollerConfig['entry_filter']['full_sync'],
                $pollerConfig['entry_filter']['incremental_sync'],
                $pollerConfig['entry_attributes_to_fetch'],
                $pollerConfig['detect_deleted'],
                $name
            ]
        );

        if ($pollerConfig['ldap_search_server_controls']) {
            $pollerDefinition->addMethodCall('setLdapSearchOptions', [LDAP_OPT_SERVER_CONTROLS, $pollerConfig['ldap_search_server_controls']]);
        }

        $id = "gtt.ad_poller.poller.$name";
        $container->setDefinition($id, $pollerDefinition);

        return $id;
    }
}
