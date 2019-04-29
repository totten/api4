<?php

namespace Civi\Api4;

/**
 * Contacts - Individuals, Organizations, Households.
 *
 * This is the central entity in the CiviCRM database, and links to
 * many other entities (Email, Phone, Participant, etc.).
 *
 * Creating a new contact requires at minimum a name or email address.
 *
 * @package Civi\Api4
 */
class Contact extends Generic\DAOEntity {

  /**
   * @return Action\Contact\Create
   */
  public static function create() {
    return new Action\Contact\Create(__CLASS__, __FUNCTION__);
  }

  public static function getChecksum() {
    return new Action\Contact\GetChecksum(__CLASS__, __FUNCTION__);
  }


  public static function validateChecksum() {
    return new Action\Contact\ValidateChecksum(__CLASS__, __FUNCTION__);
  }

}
