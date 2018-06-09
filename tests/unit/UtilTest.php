<?php

class UtilTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testModifiedAtToDbDate()
    {
        $last_modified = 'Wed, 18 Apr 2018 04:57:30 GMT';
        $db_date = \SRG\Util::to_db_date($last_modified);
        $this->assertEquals('2018-04-18 04:57:30', $db_date);
    }

    public function testGetEnv() {
        $this->assertEquals(NULL, getenv('SRG_DOESNT_EXIST'));
        $this->assertEquals(TRUE, !getenv('SRG_DOESNT_EXIST'));
        $this->assertEquals('test', getenv('APP_ENV'));
    }
}