<?php

class ResumptionTokenCest
{
    protected function _before(UnitTester $I) {

    }

    protected function _after(UnitTester $I) {
      
    }

    // tests
    public function testCleanup(UnitTester $I) {
        $date = (new \DateTime())->modify('-3 hours');
        $I->haveInDatabase('resumption_tokens', [
            'identifier' => '1234',
            'created_at' => \SRG\Util::to_db_date($date)
        ]);
        $date = (new \DateTime())->modify('-1 hours');
        $I->haveInDatabase('resumption_tokens', [
            'identifier' => '5678',
            'created_at' => \SRG\Util::to_db_date($date)
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
        $identifier = \SRG\ResumptionToken::save_state(1, 'ListRecords', $original_state);
        $I->seeInDatabase('resumption_tokens', [
            'identifier' => $identifier,
            'created_at <=' => \SRG\Util::to_db_date('now'),
            'created_at >' => \SRG\Util::to_db_date('yesterday')
        ]);

        $retrieved_state = \SRG\ResumptionToken::load_state(1, 'ListRecords', $identifier)['state'];
        $I->assertEquals($original_state, $retrieved_state);
    }
}