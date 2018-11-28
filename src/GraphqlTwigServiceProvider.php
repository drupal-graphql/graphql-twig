<?php

namespace Drupal\graphql_twig;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider to inject a custom derivation of `TwigEnvironment`.
 */
class GraphqlTwigServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Replace the twig environment with the GraphQL enhanced one.
    $container->getDefinition('twig')
      ->setClass(GraphQLTwigEnvironment::class)
      ->addArgument(new Reference('graphql.query_processor'))
      ->addArgument(new Reference('renderer'));

    // Inject our own argument resolver if it's availabble (in Drupal 8.6).
    if ($container->hasDefinition('http_kernel.controller.argument_resolver')) {
      $def = $container->getDefinition('http_kernel.controller.argument_resolver');
      $argumentResolvers = $def->getArgument(1);
      $argumentResolvers[] = new Reference('argument_resolver.graphql_twig');
      $def->setArgument(1, $argumentResolvers);
    }
  }

}
