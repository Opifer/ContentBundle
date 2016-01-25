<?php

namespace Opifer\ContentBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OpiferContentExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('blocks.yml');
        $loader->load('services.yml');
    }

    /**
     * Simplifying parameter syntax
     *
     * @param  array $config
     * @return array
     */
    public function getParameters(array $config)
    {
        $params = [
            'opifer_content.content_class' => $config['content']['class'],
            'opifer_content.directory_class' => $config['directory_class'],
            'opifer_content.layout_class' => $config['layout_class'],
            'opifer_content.content_index_view' => $config['content']['views']['index'],
            'opifer_content.content_new_view' => $config['content']['views']['new'],
            'opifer_content.content_edit_view' => $config['content']['views']['edit'],
            'opifer_content.content_details_view' => $config['content']['views']['details'],
            'opifer_content.content_history_view' => $config['content']['views']['history'],
        ];

        return $params;
    }

    /**
     * Prepend our config before other bundles, so we can preset
     * their config with our parameters
     *
     * @param  ContainerBuilder $container
     *
     * @return void
     */
    public function prepend(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setAlias('opifer.content.content_manager', $config['content_manager']);
        $container->setAlias('opifer.content.directory_manager', $config['directory_manager']);

        $parameters = $this->getParameters($config);
        foreach ($parameters as $key => $value) {
            $container->setParameter($key, $value);
        }

        foreach ($container->getExtensions() as $name => $extension) {
            switch ($name) {
                case 'doctrine':
                    $container->prependExtensionConfig($name,  [
                        'orm' => [
                            'resolve_target_entities' => [
                                'Opifer\ContentBundle\Model\DirectoryInterface' => $config['directory_class'],
                                'Opifer\ContentBundle\Model\ContentInterface' => $config['content']['class'],
                            ],
                        ],
                    ]);
                    break;
            }
        }
    }
}
