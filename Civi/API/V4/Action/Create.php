<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */
namespace Civi\API\V4\Action;
use Civi\API\Result;
use Civi\API\V4\Action;

/**
 * Base class for all create actions.
 *
 * @method $this setValues(array) Set all field values.
 */
class Create extends Action {

  /**
   * Field values to set
   *
   * @var array
   */
  protected $values = array();

  /**
   * Bao object based on the entity
   *
   * @var object
   */
  protected $bao;

  /**
   * Action constructor.
   * @param string $entity
   */
  public function __construct($entity) {
    parent::__construct($entity);
    $bao_name = $this->getBaoName();
    $this->bao = new $bao_name();
  }

  /**
   * Set a field value for the created object.
   *
   * @param string $key
   * @param mixed $value
   * @return $this
   */
  public function setValue($key, $value) {
    $this->values[$key] = $value;
    \Civi::log()->debug('setting $key: '.json_encode($key,JSON_PRETTY_PRINT));
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $create_params = $this->getParams()['values'];
    // get a bao back from the standard factory method
    $create_result = $this->bao->create($create_params);
    // trim back the junk and just get the array:
    $result_as_array = $this->baoToArray($create_result);
    // fixme should return a single row array???
    $result->exchangeArray($result_as_array);
  }

}
