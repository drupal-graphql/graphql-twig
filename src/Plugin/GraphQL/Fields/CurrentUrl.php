<?php

namespace Drupal\graphql_twig\Plugin\GraphQL\Fields;

use Drupal\Core\Url;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @GraphQLField(
 *   id = "current_url",
 *   name = "currentUrl",
 *   description = "Provides current URL at GraphQL root level.",
 *   type = "Url",
 *   arguments = {},
 *   secure = true,
 *   parents = {
 *     "Root",
 *   },
 *   response_cache_max_age = 0,
 * )
 */
class CurrentUrl extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    yield Url::fromRoute('<current>');
  }

}
