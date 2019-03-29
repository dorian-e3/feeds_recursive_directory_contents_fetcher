<?php

namespace Drupal\feeds_recursive_directory_contents_fetcher\Feeds\Fetcher\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * The configuration form for http fetchers.
 */
class FeedsRecursiveDirectoryContentsFetcherForm extends ExternalPluginFormBase
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {

        // Local Directory
        $form['local_dir'] = [
            '#title' => $this->t('Local directory to list contents of'),
            '#type' => 'textfield',
            '#default_value' => $this->plugin->getConfiguration('local_dir'),
            '#required' => TRUE,
        ];

        $form['#validate'] = array();

        return $form;
    }

}
