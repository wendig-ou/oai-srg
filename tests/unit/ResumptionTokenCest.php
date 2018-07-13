<?php

class ResumptionTokenCest
{
    protected function _before(UnitTester $I) {

    }

    protected function _after(UnitTester $I) {
      
    }

    // tests
    public function testCleanup(UnitTester $I) {
        $I->haveInDatabase('resumption_tokens', [
            'identifier' => '1234',
            'created_at' => (new \DateTime())->modify('-25 hours')->format('Y-m-d H:i:s')
        ]);
        $I->haveInDatabase('resumption_tokens', [
            'identifier' => '5678',
            'created_at' => (new \DateTime())->modify('-23 hours')->format('Y-m-d H:i:s')
        ]);
        $I->haveInDatabase('resumption_tokens', [
            'identifier' => '9999',
        ]);

        \SRG\ResumptionToken::cleanup();

        $I->dontSeeInDatabase('resumption_tokens', ['identifier' => '1234']);
        $I->seeInDatabase('resumption_tokens', ['identifier' => '5678']);
        $I->dontSeeInDatabase('resumption_tokens', ['identifier' => '9999']);
    }

    public function testSerialization(UnitTester $I) {
        $original_state = ['x' => 12, 'y' => [1,2,3]];
        $identifier = \SRG\ResumptionToken::save_state($original_state);
        $I->seeInDatabase('resumption_tokens', [
            'identifier' => $identifier,
            'created_at <=' => \SRG\Util::to_db_date('now'),
            'created_at >' => \SRG\Util::to_db_date('yesterday')
        ]);

        $retrieved_state = \SRG\ResumptionToken::load_state($identifier);
        $I->assertEquals($original_state, $retrieved_state);
    }
}