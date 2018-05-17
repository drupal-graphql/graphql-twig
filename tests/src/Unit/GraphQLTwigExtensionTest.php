<?php

namespace Drupal\Tests\graphql_twig\Unit;

use Drupal\graphql_twig\GraphQLTwigExtension;
use Drupal\Tests\UnitTestCase;

/**
 * @group graphql_twig
 */
class GraphQLTwigExtensionTest extends UnitTestCase {

  /**
   * The twig environment.
   *
   * @var \Twig_Environment
   */
  protected $twig;

  function setUp() {
    $this->twig = new \Twig_Environment(new\Twig_Loader_Array([
      'query' => '{% graphql %}query ($arg: String!) { foo(id: [1, 2, 3], search: "test") { bar } }{% endgraphql %}',
      'simple' => '{% graphql %}query a { foo }{% endgraphql %}',
      'extend' => '{% extends "simple" %}',
      'dynamic_extend' => '{% extends simple %}',
      'override_extend' => '{% graphql %}query b { foo }{% endgraphql %}{% extends "simple" %}',
      'include' => '{% graphql %}query a { foo }{% endgraphql %}{% include "sub_fragment" with { foo: "bar" } %}',
      'embed' => '{% embed "embeddable" %}{% block test %} Override {% endblock %}{% endembed %}',
      'embeddable' => '{% graphql %}query a { foo }{% endgraphql %}{% block test %} Test {% endblock %}',
      'nested_include' => '{% graphql %}query a { foo }{% endgraphql %}{% include "fragment" with { foo: "bar" } %}',
      'dynamic_include' => '{% graphql %}query a { foo }{% endgraphql %}{% include sub_fragment with { foo: "bar" } %}',
      'fragment' => '{% graphql %}query b { foo }{% endgraphql %}{% include "sub_fragment" %}',
      'sub_fragment' => '{% graphql %}query c { foo }{% endgraphql %}',
      'extend_include' => '{% graphql %}query a { foo }{% endgraphql %}{% extends "fragment" %}',
      'recursive' => '{% graphql %}query a { ... b }{% endgraphql %}{% include "recursive_include" %}',
      'recursive_include' => '{% graphql %}fragment b on foo { bar }{% endgraphql %}{% include "recursive_include" %}',
    ]));
    $this->twig->addExtension(new GraphQLTwigExtension());
  }

  protected function assertGraphQLQuery($template, $query) {
    $template = $this->twig->loadTemplate($template);
    $this->assertTrue(method_exists($template, 'getGraphQLQuery'));
    $this->assertEquals($query, $template->getGraphQLQuery());
  }

  function testQuery() {
    $this->assertGraphQLQuery('query', 'query ($arg: String!) { foo(id: [1, 2, 3], search: "test") { bar } }');
  }

  function testExtend() {
    $this->assertGraphQLQuery('extend', 'query a { foo }');
  }

  function testDynamicExtend() {
    $this->assertGraphQLQuery('dynamic_extend', '');
  }

  function testInclude() {
    $this->assertGraphQLQuery('include', "query a { foo }\nquery c { foo }");
  }

  function testEmbed() {
    $this->assertGraphQLQuery('embed', "query a { foo }");
  }

  function testNestedInclude() {
    $this->assertGraphQLQuery('nested_include', "query a { foo }\nquery b { foo }\nquery c { foo }");
  }

  function testDynamicInclude() {
    $this->assertGraphQLQuery('dynamic_include', "query a { foo }");
  }

  function testExtendInclude() {
    $this->assertGraphQLQuery('extend_include', "query a { foo }\nquery c { foo }");
  }

  function testRecursiveInclude() {
    $this->assertGraphQLQuery('recursive', "query a { ... b }\nfragment b on foo { bar }");
  }

}
