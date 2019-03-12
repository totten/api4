<?php
namespace Civi\Api4\Generic;

use Civi\API\Exception\NotImplementedException;

/**
 * Base class for all api entities.
 *
 * When adding your own api from an extension, extend this class only
 * if your entity does not have an associated DAO. Otherwise extend DAOEntity.
 */
abstract class AbstractEntity {

  /**
   * @return \Civi\Api4\Action\GetActions
   */
  public static function getActions() {
    return new \Civi\Api4\Action\GetActions(self::getEntityName(), __FUNCTION__);
  }

  /**
   * Should return \Civi\Api4\Generic\BasicGetFieldsAction
   * @todo make this function abstract when we require php 7.
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public static function getFields() {
    throw new NotImplementedException(self::getEntityName() . ' should implement getFields action.');
  }

  /**
   * Returns a list of permissions needed to access the various actions in this api.
   *
   * @return array
   */
  public static function permissions() {
    $permissions = \CRM_Core_Permission::getEntityActionPermissions();

    // For legacy reasons the permissions are keyed by lowercase entity name
    $lcentity = _civicrm_api_get_entity_name_from_camel(self::getEntityName());
    // Merge permissions for this entity with the defaults
    return \CRM_Utils_Array::value($lcentity, $permissions, []) + $permissions['default'];
  }

  /**
   * Get entity name from called class
   *
   * @return string
   */
  protected static function getEntityName() {
    return substr(static::class, strrpos(static::class, '\\') + 1);
  }

  /**
   * Magic method to return the action object for an api.
   *
   * @param string $action
   * @param null $args
   * @return AbstractAction
   * @throws NotImplementedException
   */
  public static function __callStatic($action, $args) {
    $entity = self::getEntityName();
    // Find class for this action
    $entityAction = "\\Civi\\Api4\\Action\\$entity\\" . ucfirst($action);
    if (class_exists($entityAction)) {
      $actionObject = new $entityAction($entity, $action);
    }
    else {
      throw new NotImplementedException("Api $entity $action version 4 does not exist.");
    }
    return $actionObject;
  }

}
