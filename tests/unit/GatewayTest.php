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
    public function testRepositoryLifecycle()
    {
        $url = 'https://github.com/wendig-ou/oai-srg/raw/master/tests/_data/repository.01.xml';

        \SRG\Gateway::initiate($url);
        $this->tester->seeInDatabase('repositories', [
            'url' => $url,
            'approved' => NULL
        ]);

        \SRG\Gateway::approve($url);
        $this->tester->seeInDatabase('repositories', [
            'url' => $url,
            'approved' => TRUE
        ]);

        \SRG\Gateway::import($url);
        $repository = \SRG\Repository::find_by_url($url);
        $this->assertEquals('1', $repository->approved);
        $this->assertRegExp('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $repository->verified_at);
        $this->assertNotEmpty($repository->identify);
        $this->assertNotEmpty($repository->list_metadata_formats);
        $this->assertEquals('jondoe@oai.org', $repository->admin_email);
        $record_count = $this->tester->grabNumRecords('records', ['repository_id' => $repository->id]);
        $this->assertEquals(3, $record_count);

        \SRG\Gateway::terminate($url);
        $this->tester->dontSeeInDatabase('repositories', [
            'url' => $url
        ]);
        $record_count = $this->tester->grabNumRecords('records', ['repository_id' => $repository->id]);
        $this->assertEquals(0, $record_count);
    }
}