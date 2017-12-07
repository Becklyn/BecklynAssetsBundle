<?php

namespace Becklyn\AssetsBundle\DependencyInjection;

use Becklyn\AssetsBundle\Asset\NamespacedAsset;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class BecklynAssetsConfiguration implements ConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder ()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('becklyn_assets');

        $rootNode
            ->children()
                ->arrayNode("entries")
                    ->scalarPrototype()->end()
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(
                            function (array $paths)
                            {
                                foreach ($paths as $namespace => $path)
                                {
                                    if (1 !== \preg_match('~^' . NamespacedAsset::NAMESPACE_REGEX . '$~i', $namespace))
                                    {
                                        return true;
                                    }
                                }

                                return false;
                            }
                        )
                            ->thenInvalid("The namespaces must start with a-z and can only contain a-z and 0-9.")
                        ->end()
                    ->validate()
                        ->ifTrue(
                            function (array $paths)
                            {
                                foreach ($paths as $path)
                                {
                                    if (!\is_string($path))
                                    {
                                        return true;
                                    }

                                    if (false !== \strpos($path, "..."))
                                    {
                                        return true;
                                    }
                                }

                                return false;
                            }
                        )
                            ->thenInvalid("The entries can't be outside of the project root (and can't use '..' in their paths).")
                        ->end()
                    ->info("All entry directories, where assets are searched. Relative to `kernel.project_dir`.")
                ->end()
                ->scalarNode("public_path")
                    ->defaultValue('%kernel.project_dir%/public')
                    ->info("The absolute path to the `public/` (or `web/`) directory.")
                ->end()
                ->scalarNode("output_dir")
                    ->defaultValue('assets')
                    ->info("The relative path to the assets output dir. Relative to `public_path`.")
                ->end()
            ->end();

        return $treeBuilder;
    }
}
