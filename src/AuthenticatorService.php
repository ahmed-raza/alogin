<?php

namespace Drupal\alogin;

use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;
use Sonata\GoogleAuthenticator\FixedBitNotation;
use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleAuthenticatorInterface;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

/**
 * Class AuthenticatorService.
 */
class AuthenticatorService {

  protected $secret, $issuer, $name, $database, $configFactory;
  protected $table = 'alogin_user_settings';

  /**
   * Constructs a new AuthenticatorService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $account, Connection $database) {
    $g = new GoogleAuthenticator();
    $this->configFactory  = $config_factory;
    $this->secret         = $g->generateSecret();
    $this->issuer         = $this->configFactory->get('system.site')->get('name');
    $this->name           = $account->getAccountName();
    $this->database       = $database;
  }

  public function getQr() {
    $g = new GoogleAuthenticator();
    return $g->getURL($this->name, 'Drupal', $this->secret);
  }

  public function check($code) {
    $g = new GoogleAuthenticator();
    return $g->checkCode($this->secret, $code);
  }

  public function store($uid, $enable) {
    if ($this->exists($uid)) {
      return $this->update($uid, $enable);
    }
    return $this->new($uid, $enable);
  }

  public function exists($uid) {
    $exists = $this->database->select($this->table, 'a')
              ->fields('a')
              ->condition('uid', $uid, '=')
              ->execute()
              ->fetchAssoc();
    return $exists;
  }

  public function new($uid, $enable = TRUE) {
    $create = $this->database->insert($this->table)
              ->fields([
                'uid' => $uid,
                'enabled' => $enable
              ])->execute();
    return $create;
  }

  public function update($uid, $enable) {
    $create = $this->database->update($this->table)
              ->fields([
                'enabled' => $enable
              ])
              ->condition('uid', $uid, '=')
              ->execute();
    return $create;
  }

  public function is_enabled($uid) {
    $enabled = $this->database->select($this->table, 'a')
              ->fields('a', ['enabled'])
              ->condition('uid', $uid, '=')
              ->execute()
              ->fetchAssoc();
    return $enabled ? $enabled['enabled'] : false;
  }

}
