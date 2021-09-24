<?php

namespace Drupal\alogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Class AloginController.
 */
class AloginController extends ControllerBase {

  /**
   * Build.
   *
   * @return string
   *   Return Hello string.
   */
  public function build(AccountInterface $user) {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: build')
    ];
  }

}
