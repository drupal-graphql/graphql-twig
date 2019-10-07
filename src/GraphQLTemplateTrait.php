<?php

namespace Drupal\graphql_twig;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\graphql\GraphQL\Execution\QueryProcessor;
use GraphQL\Server\OperationParams;

/**
 * Trait that will be attached to all GraphQL enabled Twig templates.
 */
trait GraphQLTemplateTrait {

  /**
   * @return bool
   */
  abstract public static function hasGraphQLOperations();

  /**
   * @var string
   */
  abstract public static function rawGraphQLQuery();

  /**
   * @return string
   */
  abstract public static function rawGraphQLParent();

  /**
   * @return string[]
   */
  abstract public static function rawGraphQLIncludes();

  /**
   * @return string[]
   */
  abstract public static function rawGraphQLArguments();

  /**
   * The GraphQL query processor.
   *
   * @var \Drupal\graphql\GraphQL\Execution\QueryProcessor
   */
  protected $queryProcessor;

  /**
   * Inject the query processor.
   *
   * @param \Drupal\graphql\GraphQL\Execution\QueryProcessor $queryProcessor
   *   The query processor instance.
   */
  public function setQueryProcessor(QueryProcessor $queryProcessor) {
    $this->queryProcessor = $queryProcessor;
  }

  /**
   * {@inheritdoc}
   */
  public function display(array $context, array $blocks = array()) {
    if (!static::hasGraphQLOperations()) {
      parent::display($context, $blocks);
      return;
    }

    if (isset($context['graphql_arguments'])) {
      $context = $context['graphql_arguments'];
    }

    $query = trim($this->getGraphQLQuery());

    if (!$query) {
      parent::display($context, $blocks);
      return;
    }

    $arguments = [];
    foreach (static::rawGraphQLArguments() as $var) {
      if (isset($context[$var])) {
        $arguments[$var] = $context[$var] instanceof EntityInterface ? $context[$var]->id() : $context[$var];
      }
    }


    $queryResult = $this->env->getQueryProcessor()->processQuery('default:default', OperationParams::create([
      'query' => $query,
      'variables' => $arguments,
    ]));

    $build = [
      '#cache' => [
        'contexts' => $queryResult->getCacheContexts(),
        'tags' => $queryResult->getCacheTags(),
        'max-age' => $queryResult->getCacheMaxAge(),
      ],
    ];

    $this->env->getRenderer()->render($build);

    $config = \Drupal::config('graphql_twig.settings');
    $debug_placement = $config->get('debug_placement');

    if ($this->env->isDebug()) {
      // Auto-attach the debug assets if necessary.
      $template_attached = ['#attached' => ['library' => ['graphql_twig/debug']]];
      $this->env->getRenderer()->render($template_attached);
    }

    if ($this->env->isDebug() && $debug_placement == 'wrapped') {
      printf(
        '<div class="%s" data-graphql-query="%s" data-graphql-variables="%s">',
        'graphql-twig-debug-wrapper',
        htmlspecialchars($query),
        htmlspecialchars(json_encode($arguments))
      );
    }

    if ($queryResult->errors) {
      print('<ul class="graphql-twig-errors">');
      foreach ($queryResult->errors as $error) {
        printf('<li>%s</li>', $error->message);
      }
      print('</ul>');
    }
    else {
      $context['graphql'] = $queryResult->data;
      if ($this->env->isDebug() && $debug_placement == 'inside') {
        $context['graphql_debug'] = [
          '#markup' => sprintf(
            '<div class="graphql-twig-debug-child"><div class="%s" data-graphql-query="%s" data-graphql-variables="%s"></div></div>',
            'graphql-twig-debug-wrapper',
            htmlspecialchars($query),
            htmlspecialchars(json_encode($arguments))
          ),
        ];

        // Add the debug parent class to the element.
        /** @var \Drupal\Core\Template\Attribute $attributes */
        $attributes = $context['attributes'];
        $attributes->addClass('graphql-twig-debug-parent');
      }

      parent::display($context, $blocks);
    }

    if ($this->env->isDebug() && $debug_placement == 'wrapped') {
      print('</div>');
    }
  }

  /**
   * Recursively build the GraphQL query.
   *
   * Builds the templates GraphQL query by iterating through all included or
   * embedded templates recursively.
   */
  public function getGraphQLQuery() {

    $query = '';
    $includes = [];

    if ($this instanceof \Twig_Template) {
      $query = $this->getGraphQLFragment();

      $includes = array_keys($this->getGraphQLIncludes());

      // Recursively collect all included fragments.
      $includes = array_map(function ($template) {
        return $this->env->loadTemplate($template)->getGraphQLFragment();
      }, $includes);

      // Always add includes from parent templates.
      if ($parent = $this->getGraphQLParent()) {
        $includes += array_map(function ($template) {
          return $this->env->loadTemplate($template)->getGraphQLQuery();
        }, array_keys($parent->getGraphQLIncludes()));
      }
    }


    return implode("\n", [-1 => $query] + $includes);
  }

  /**
   * Get the files parent template.
   *
   * @return \Twig_Template|null
   *   The parent template or null.
   */
  protected function getGraphQLParent() {
    return static::rawGraphQLParent() ? $this->env->loadTemplate(static::rawGraphQLParent()) : NULL;
  }

  /**
   * Retrieve the files graphql fragment.
   *
   * @return string
   *   The GraphQL fragment.
   */
  public function getGraphQLFragment() {
    // If there is no query for this template, try to get one from the
    // parent template.
    if (!($query = static::rawGraphQLQuery()) && ($parent = $this->getGraphQLParent())) {
      $query = $parent->getGraphQLFragment();
    }
    return $query;
  }

  /**
   * Retrieve a list of all direct or indirect included templates.
   *
   * @param string[] $recursed
   *   The list of templates already recursed into. Used internally.
   *
   * @return string[]
   *   The list of included templates.
   */
  public function getGraphQLIncludes(&$recursed = []) {

    $includes = array_flip(static::rawGraphQLIncludes());
    foreach ($includes as $include => $key) {
      if (in_array($include, $recursed)) {
        continue;
      }

      $recursed[] = $include;

      // TODO: operate on template class instead.
      $includes += $this->env->loadTemplate($include)->getGraphQLIncludes($recursed);
    }

    return $includes;
  }
}
