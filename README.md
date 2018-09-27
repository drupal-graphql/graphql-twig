# GraphQL Twig for Drupal

[![Build Status](https://img.shields.io/travis/drupal-graphql/graphql-twig.svg)](https://travis-ci.org/drupal-graphql/graphql-twig)
[![Code Coverage](https://img.shields.io/codecov/c/github/drupal-graphql/graphql-twig.svg)](https://codecov.io/gh/drupal-graphql/graphql-twig)
[![Code Quality](https://img.shields.io/scrutinizer/g/drupal-graphql/graphql-twig.svg)](https://scrutinizer-ci.com/g/drupal-graphql/graphql-twig/?branch=8.x-1.x)

The GraphQL Twig module allows you to inject data into Twig templates by simply adding
a GraphQL query. No site building or pre-processing necessary.

## Simple example 

The *"Powered by Drupal"* block only gives credit to Drupal, but what's a website
without administrators and users? Right. Lets fix that.

Place the following template override for the *"Powered by Drupal"* block in your theme:

```twig
{#graphql
query {
  admin:userById(id: "1") {
    uid
    name
  }
  user:currentUserContext {
    uid
  }
}
#}

{% extends '@bartik/block.html.twig' %}
{% block content %}
  {% embed '@graphql_twig/query.html.twig' %}
    {% block content %}
      {% set admin = graphql.admin %}
      {% set user = graphql.user %}
      <div{{ content_attributes.addClass('content') }}>
        {{ content }} and
          {% if user.uid == admin.uid %}
            you, {{ admin.name }}.
          {% else %}
            you, appreciated anonymous visitor.
          {% endif %}
      </div>
    {% endblock %}
  {% endembed %}
{% endblock %}
```

For the sake of an example, we assumed that you based your theme on Bartik (which you probably didn't), but
what else happens here? In the `{#graphql ... #}` comment block we annotated a simple GraphQL query, that will
be executed in an additional preprocessing step, to populate your template with an additional variable called
`graphql` that will contain the result. We injected additional information into our template without the need
to fall back to site building or manual preprocessing.
The templates content is wrapped in an embed of `@graphql_twig/query.html.twig`. This is  not
necessary, but will emit debug information if Twig's debug setting is set to `true`.

This is the basic concept of GraphQL in Twig. Additionally it will collect query fragments from
included templates, automatically try to match template variables to query arguments and enable you to tap
into all features of the [Drupal GraphQL] module.

More documentation coming soon!

[Drupal GraphQL]: https://github.com/drupal-graphql/graphql
