<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ItemsFixture
 *
 */
class ItemsFixture extends TestFixture
{

	/**
	 * Fields
	 *
	 * @var array
	 */
	// @codingStandardsIgnoreStart
	public $fields = [
	'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
	'user_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
	'nummer' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
	'bezeichnung' => ['type' => 'string', 'length' => 50, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
	'groesse' => ['type' => 'string', 'length' => 10, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
	'preis' => ['type' => 'decimal', 'length' => 4, 'precision' => 2, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => ''],
	'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
	'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
	'_indexes' => [
	'user_key' => ['type' => 'index', 'columns' => ['user_id'], 'length' => []],
	],
	'_constraints' => [
	'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
	'items_ibfk_1' => ['type' => 'foreign', 'columns' => ['user_id'], 'references' => ['users', 'id'], 'update' => 'restrict', 'delete' => 'restrict', 'length' => []],
	],
	'_options' => [
	'engine' => 'InnoDB',
	'collation' => 'latin1_swedish_ci'
			],
			];
			// @codingStandardsIgnoreEnd

			/**
			 * Records
			 *
			 * @var array
			 */
			public $records = [
			[
			'id' => 1,
			'user_id' => 1,
			'nummer' => 1,
			'bezeichnung' => 'Lorem ipsum dolor sit amet',
			'groesse' => 'Lorem ip',
			'preis' => '',
			'created' => '2015-06-23 20:43:25',
			'modified' => '2015-06-23 20:43:25'
					],
					];
}
