<?php

namespace Drupal\graphql_twig;

/**
 * A Twig node for collecting GraphQL query fragments in twig templates.
 */
class GraphQLFragmentNode extends \Twig_Node {

  /**
   * The fragment string.
   *
   * @var string
   */
  public $fragment = "";

  /**
   * GraphQLFragmentNode constructor.
   *
   * @param string $fragment
   *   The query fragment.
   *
   * @throws \GraphQL\Error\SyntaxError
   *   Thrown if the GraphQL query is not valid.
   */
  public function __construct($fragment) {
    $this->fragment = $fragment;
    parent::__construct();
  }

}
