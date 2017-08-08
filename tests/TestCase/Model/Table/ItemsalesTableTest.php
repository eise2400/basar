<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ItemsalesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ItemsalesTable Test Case
 */
class ItemsalesTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Model\Table\ItemsalesTable
     */
    public $Itemsales;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.itemsales',
        'app.users',
        'app.items',
        'app.registers',
        'app.sync',
        'app.reg_items'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Itemsales') ? [] : ['className' => ItemsalesTable::class];
        $this->Itemsales = TableRegistry::get('Itemsales', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Itemsales);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
