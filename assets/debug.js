(function ($, Drupal) {
  Drupal.behaviors.graphqlTwigDebug = {
    attach: function (context, settings) {
      $('.graphql-twig-debug-wrapper', context).once('graphql-debug').each(function () {
        var query = $(this).attr('data-graphql-query');
        var variables = $(this).attr('data-graphql-variables');
        var $form = $('<form method="post" target="_blank"></form>').attr('action', Drupal.url('graphql/explorer')).appendTo(this);
        var txt = document.createElement('textarea');
        txt.innerHTML = variables;
        variables = txt.value;
        $('<input type="hidden" name="query"/>').val(query).appendTo($form);
        $('<input type="hidden" name="variables"/>').val(variables).appendTo($form);
        $('<input type="submit" class="graphql-twig-debug-button" value="Inspect GraphQL query"/>').appendTo($form);
      });
    }
  };
}(jQuery, Drupal));
