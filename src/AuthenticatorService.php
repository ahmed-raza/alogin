<?php

namespace Drupal\alogin;

use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Config\ConfigFactory;
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

  protected $secret, $issuer, $currentUser, $currentUid = 0, $database, $configFactory, $tempstorePrivate;
  protected $table = 'alogin_user_settings';

  /**
   * Constructs a new AuthenticatorService object.
   */
  public function __construct(ConfigFactory $configFactory, PrivateTempStoreFactory $tempstorePrivate, AccountInterface $account, Connection $database) {
    $g = new GoogleAuthenticator();
    $this->tempstorePrivate = $tempstorePrivate;
    $this->configFactory    = $configFactory;
    $this->issuer           = $this->configFactory->get('system.site')->get('name');
    $this->currentUser      = $account;
    $this->database         = $database;
    $this->currentUid       = $this->currentUser->isAuthenticated() ? $this->currentUser->id() : $this->tempstorePrivate->get('alogin')->get('uid');

    if (!$this->getSecret($this->currentUid) && !$this->tempstorePrivate->get('alogin')->get('secret')) {
      $this->tempstorePrivate->get('alogin')->set('secret', $g->generateSecret());
    }

    $this->secret           = $this->getSecret($this->currentUid) ? $this->getSecret($this->currentUid) : $this->tempstorePrivate->get('alogin')->get('secret');
  }

  public function getQr() {
    $g = new GoogleAuthenticator();
    return $g->getURL($this->currentUser->getDisplayName(), str_replace(' ', '', $this->issuer) , $this->secret);
  }

  public function check($code) {
    $g = new GoogleAuthenticator();
    return $g->checkCode($this->secret, $code);
  }

  public function store($enable) {
    if ($this->exists($this->currentUser->id())) {
      return $this->update($enable);
    }
    return $this->new($enable);
  }

  public function exists() {
    $exists = $this->database->select($this->table, 'a')
              ->fields('a')
              ->condition('uid', $this->currentUser->id(), '=')
              ->execute()
              ->fetchAssoc();
    return $exists;
  }

  public function new($enable = TRUE) {
    $secret = $this->secret;
    $create = $this->database->insert($this->table)
              ->fields([
                'uid' => $this->currentUser->id(),
                'secret' => $secret,
                'enabled' => $enable
              ])->execute();
    return $create;
  }

  public function update($enable) {
    $secret = $enable ? $this->secret : '';
    $update = $this->database->update($this->table)
              ->fields([
                'secret' => $secret,
                'enabled' => $enable
              ])
              ->condition('uid', $this->currentUser->id(), '=')
              ->execute();
    return $update;
  }

  public function is_enabled($uid) {
    $enabled = $this->database->select($this->table, 'a')
              ->fields('a', ['enabled'])
              ->condition('uid', $uid, '=')
              ->execute()
              ->fetchAssoc();
    return $enabled ? $enabled['enabled'] : false;
  }

  protected function getSecret($uid) {
    $secret = $this->database->select($this->table, 'a')
              ->fields('a', ['secret'])
              ->condition('uid', $uid, '=')
              ->execute()
              ->fetchAssoc();
    return $secret ? $secret['secret'] : false;
  }

}
