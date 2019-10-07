<?php

namespace Drupal\graphql_twig\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure graphql twig settings
 *
 * @internal
 */
class GraphQLTwigSettingsForm extends ConfigFormBase {


  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'graphql_twig_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['graphql_twig.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('graphql_twig.settings');
    $debug_placement = $site_config->get('debug_placement');

    $form['debug'] = [
      '#type' => 'details',
      '#title' => t('Debug'),
      '#open' => TRUE,
    ];
    $form['debug']['debug_placement'] = [
      '#type' => 'select',
      '#title' => t('Debug placement'),
      '#options' => [
        'wrapped' => $this->t('wrapped'),
        'inside' => $this->t('inside'),
      ],
      '#default_value' => $debug_placement,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('graphql_twig.settings')
      ->set('debug_placement', $form_state->getValue('debug_placement'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
