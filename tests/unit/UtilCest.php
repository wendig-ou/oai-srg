<?php

class UtilCest
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before(UnitTester $I)
    {
    }

    protected function _after(UnitTester $I)
    {
    }

    // tests
    public function testModifiedAtToDbDate(UnitTester $I)
    {
        $last_modified = 'Wed, 18 Apr 2018 04:57:30 GMT';
        $db_date = \SRG\Util::to_db_date($last_modified);
        $I->assertEquals('2018-04-18 04:57:30', $db_date);
    }

    public function testGetEnv(UnitTester $I) {
        $I->assertEquals(NULL, getenv('SRG_DOESNT_EXIST'));
        $I->assertEquals(TRUE, !getenv('SRG_DOESNT_EXIST'));
        $I->assertEquals('test', getenv('APP_ENV'));
    }

    public function testToOaiDate(UnitTester $I) {
        $date = 'Wed, 18 Apr 2018 04:57:30 GMT';
        $I->assertEquals('2018-04-18T04:57:30Z', \SRG\Util::to_oai_date($date));
    }
}