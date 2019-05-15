<?php

namespace Civi\Test\Api4\Entity;

use Civi\Api4\Generic\AbstractEntity;
use Civi\Api4\Entity;
use Civi\Test\Api4\Service\TestCreationParameterProvider;
use Civi\Test\Api4\Traits\TableDropperTrait;
use Civi\Test\Api4\UnitTestCase;

/**
 * @group headless
 */
class ConformanceTest extends UnitTestCase {

  use TableDropperTrait;
  use \Civi\Test\Api4\Traits\OptionCleanupTrait {
    setUp as setUpOptionCleanup;
  }

  /**
   * @var TestCreationParameterProvider
   */
  protected $creationParamProvider;

  /**
   * Set up baseline for testing
   */
  public function setUp() {
    $tablesToTruncate = [
      'civicrm_custom_group',
      'civicrm_custom_field',
      'civicrm_group',
      'civicrm_event',
      'civicrm_participant',
    ];
    $this->dropByPrefix('civicrm_value_myfavorite');
    $this->cleanup(['tablesToTruncate' => $tablesToTruncate]);
    $this->setUpOptionCleanup();
    $this->loadDataSet('ConformanceTest');
    $this->creationParamProvider = \Civi::container()->get('test.param_provider');
    parent::setUp();
    // calculateTaxAmount() for contribution triggers a deprecation notice
    \PHPUnit_Framework_Error_Deprecated::$enabled = FALSE;
  }

  public function getEntities() {
    return Entity::get()->setCheckPermissions(FALSE)->execute()->column('name');
  }

  /**
   * Fixme: This should use getEntities as a dataProvider but that fails for some reason
   */
  public function testConformance() {
    $entities = $this->getEntities();
    $this->assertNotEmpty($entities);

    foreach ($entities as $data) {
      $entity = $data;
      $entityClass = 'Civi\Api4\\' . $entity;

      $actions = $this->checkActions($entityClass);

      // Go no further if it's not a CRUD entity
      if (array_diff(['get', 'create', 'update', 'delete'], array_keys($actions))) {
        continue;
      }

      $this->checkFields($entityClass, $entity);
      $id = $this->checkCreation($entity, $entityClass);
      $this->checkGet($entityClass, $id, $entity);
      $this->checkGetCount($entityClass, $id, $entity);
      $this->checkUpdateFailsFromCreate($entityClass, $id);
      $this->checkWrongParamType($entityClass);
      $this->checkDeleteWithNoId($entityClass);
      $this->checkDeletion($entityClass, $id);
      $this->checkPostDelete($entityClass, $id, $entity);
    }
  }

  /**
   * @param string $entityClass
   * @param $entity
   */
  protected function checkFields($entityClass, $entity) {
    $fields = $entityClass::getFields()
      ->setCheckPermissions(FALSE)
      ->setIncludeCustom(FALSE)
      ->execute()
      ->indexBy('name');

    $errMsg = sprintf('%s is missing required ID field', $entity);
    $subset = ['data_type' => 'Integer'];

    $this->assertArraySubset($subset, $fields['id'], $errMsg);
  }

  /**
   * @param string $entityClass
   */
  protected function checkActions($entityClass) {
    $actions = $entityClass::getActions()
      ->setCheckPermissions(FALSE)
      ->execute()
      ->indexBy('name');

    $this->assertNotEmpty($actions);
    return (array) $actions;
  }

  /**
   * @param string $entity
   * @param AbstractEntity|string $entityClass
   *
   * @return mixed
   */
  protected function checkCreation($entity, $entityClass) {
    $requiredParams = $this->creationParamProvider->getRequired($entity);
    $createResult = $entityClass::create()
      ->setValues($requiredParams)
      ->setCheckPermissions(FALSE)
      ->execute()
      ->first();

    $this->assertArrayHasKey('id', $createResult, "create missing ID");
    $id = $createResult['id'];

    $this->assertGreaterThanOrEqual(1, $id, "$entity ID not positive");

    return $id;
  }

  /**
   * @param AbstractEntity|string $entityClass
   * @param int $id
   */
  protected function checkUpdateFailsFromCreate($entityClass, $id) {
    $exceptionThrown = '';
    try {
      $entityClass::create()
        ->setCheckPermissions(FALSE)
        ->addValue('id', $id)
        ->execute();
    }
    catch (\API_Exception $e) {
      $exceptionThrown = $e->getMessage();
    }
    $this->assertContains('id', $exceptionThrown);
  }

  /**
   * @param AbstractEntity|string $entityClass
   * @param int $id
   * @param string $entity
   */
  protected function checkGet($entityClass, $id, $entity) {
    $getResult = $entityClass::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $id)
      ->execute();

    $errMsg = sprintf('Failed to fetch a %s after creation', $entity);
    $this->assertEquals($id, $getResult->first()['id'], $errMsg);
    $this->assertEquals(1, $getResult->count(), $errMsg);
  }

  /**
   * @param AbstractEntity|string $entityClass
   * @param int $id
   * @param string $entity
   */
  protected function checkGetCount($entityClass, $id, $entity) {
    $getResult = $entityClass::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $id)
      ->selectRowCount()
      ->execute();
    $errMsg = sprintf('%s getCount failed', $entity);
    $this->assertEquals(1, $getResult->count(), $errMsg);

    $getResult = $entityClass::get()
      ->setCheckPermissions(FALSE)
      ->selectRowCount()
      ->execute();
    $errMsg = sprintf('%s getCount failed', $entity);
    $this->assertGreaterThanOrEqual(1, $getResult->count(), $errMsg);
  }

  /**
   * @param AbstractEntity|string $entityClass
   */
  protected function checkDeleteWithNoId($entityClass) {
    $exceptionThrown = '';
    try {
      $entityClass::delete()
        ->execute();
    }
    catch (\API_Exception $e) {
      $exceptionThrown = $e->getMessage();
    }
    $this->assertContains('required', $exceptionThrown);
  }

  /**
   * @param AbstractEntity|string $entityClass
   */
  protected function checkWrongParamType($entityClass) {
    $exceptionThrown = '';
    try {
      $entityClass::get()
        ->setCheckPermissions('nada')
        ->execute();
    }
    catch (\API_Exception $e) {
      $exceptionThrown = $e->getMessage();
    }
    $this->assertContains('checkPermissions', $exceptionThrown);
    $this->assertContains('type', $exceptionThrown);
  }

  /**
   * @param AbstractEntity|string $entityClass
   * @param int $id
   */
  protected function checkDeletion($entityClass, $id) {
    $deleteResult = $entityClass::delete()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $id)
      ->execute();

    // should get back an array of deleted id
    $this->assertEquals([['id' => $id]], (array) $deleteResult);
  }

  /**
   * @param AbstractEntity|string $entityClass
   * @param int $id
   * @param string $entity
   */
  protected function checkPostDelete($entityClass, $id, $entity) {
    $getDeletedResult = $entityClass::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $id)
      ->execute();

    $errMsg = sprintf('Entity "%s" was not deleted', $entity);
    $this->assertEquals(0, count($getDeletedResult), $errMsg);
  }

}
