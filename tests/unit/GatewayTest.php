<?php

class GatewayTest extends \Codeception\Test\Unit
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
    public function testSomeFeature()
    {
        \SRG\Gateway::initiate('https://static.wendig.io/tmp/sample.xml');
        $this->tester->seeInDatabase('repositories', [
            'url' => 'https://static.wendig.io/tmp/sample.xml',
            'approved' => NULL
        ]);

        \SRG\Gateway::approve('https://static.wendig.io/tmp/sample.xml');
        $this->tester->seeInDatabase('repositories', [
            'url' => 'https://static.wendig.io/tmp/sample.xml',
            'approved' => TRUE
        ]);

        \SRG\Gateway::extract('https://static.wendig.io/tmp/sample.xml');
        $repository = \SRG\Repository::find_by_url('https://static.wendig.io/tmp/sample.xml');
        $this->assertEquals('1', $repository->approved);
        $this->assertRegExp('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $repository->verified_at);
        $this->assertNotEmpty($repository->identify);
        $this->assertNotEmpty($repository->list_metadata_formats);
        $this->assertEquals('jondoe@oai.org', $repository->admin_email);
    }
}