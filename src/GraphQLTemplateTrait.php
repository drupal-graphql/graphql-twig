<?php

namespace Drupal\graphql_twig;

/**
 * Trait that will be attached to all GraphQL enabled Twig templates.
 */
trait GraphQLTemplateTrait {

  public function display(array $context, array $blocks = array()) {
    if (!isset($context['graphql']) || !$context['graphql']['debug']) {
      parent::display($context, $blocks);
      return;
    }

    echo '<div class="graphql-twig-debug-wrapper" data-query="' . htmlspecialchars($context['graphql']['query']). '" data-variables="' . htmlspecialchars($context['graphql']['variables']) . '">';
    if (isset($context['graphql']['errors']) && $context['graphql']['errors']) {
      echo '<ul class="graphql-twig-errors">';
      foreach ($context['graphql']['errors'] as $error) {
        echo '<li>' . $error->message . '</li>';
      }
      echo '</ul>';
    }
    parent::display($context, $blocks);
    echo '</div>';
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
        return $this->loadTemplate($template)->getGraphQLFragment();
      }, $includes);

      // Always add includes from parent templates.
      if ($parent = $this->getGraphQLParent()) {
        $includes += array_map(function ($template) {
          return $this->loadTemplate($template)->getGraphQLQuery();
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
    return $this->graphqlParent ? $this->loadTemplate($this->graphqlParent) : NULL;
  }

  /**
   * Retrieve the files graphql fragment.
   *
   * @return string
   *   The GraphQL fragment.
   */
  public function getGraphQLFragment() {
    $query = '';
    // If there is no query for this template, try to get one from the
    // parent template.
    if ($this->graphqlQuery) {
      $query = $this->graphqlQuery;
    }
    elseif ($parent = $this->getGraphQLParent()) {
      $query = $parent->getGraphQLFragment();
    }
    return $query;
  }

  /**
   * Retrieve a list of all direct or indirect included templates.
   *
   * @return string[]
   *   The list of included templates.
   */
  public function getGraphQLIncludes() {
    $includes = array_flip($this->graphqlIncludes);
    if ($includes) {
      foreach ($includes as $include => $key) {
        $includes += $this->loadTemplate($include)->getGraphQLIncludes();
      }
    }
    return $includes;
  }
}
