<?php

class GatewayCest
{
    protected function _before() {}
    protected function _after() {}

    // tests
    public function testRepositoryLifecycle(UnitTester $I)
    {
        $url = 'http://localhost:3002/repository.01.xml';

        \SRG\Gateway::initiate($url);
        $I->seeInDatabase('repositories', [
            'url' => $url,
            'approved' => NULL
        ]);

        \SRG\Gateway::approve($url);
        $I->seeInDatabase('repositories', [
            'url' => $url,
            'approved' => TRUE
        ]);

        $importResult = \SRG\Gateway::import($url);

        $repository = \SRG\Repository::find_by_url($url);
        $I->assertTrue($importResult);
        $repository = \SRG\Repository::find_by_url($url);
        $I->assertEquals('1', $repository->approved);
        $I->assertEquals('oai_dc,oai_rfc1807', $repository->prefixes);
        $I->assertRegExp('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $repository->verified_at);
        $I->assertNotEmpty($repository->identify);
        $I->assertNotEmpty($repository->list_metadata_formats);
        $I->assertEquals('jondoe@oai.org', $repository->admin_email);
        $record_count = $I->grabNumRecords('records', ['repository_id' => $repository->id]);
        $I->assertEquals(4, $record_count);

        \SRG\Gateway::terminate($url);
        $I->dontSeeInDatabase('repositories', [
            'url' => $url
        ]);
        $record_count = $I->grabNumRecords('records', ['repository_id' => $repository->id]);
        $I->assertEquals(0, $record_count);
    }

    public function testValidateRepositoryUrl(UnitTester $I) {
        $message = '/Repository urls must have scheme, host and path. Also they cannot contain a query string nor a hash fragment./';

        $url = 'https://wendig.io/my/doc.xml?version=2';
        try {
            \SRG\Gateway::initiate($url);
            $I->fail("Expected exception \SRG\Exception not thrown");
        } catch(\SRG\Exception $e) {
            $I->assertRegExp($message, $e->getMessage());
        }

        $url = 'https://wendig.io/my/doc.xml#beta';
        try {
            \SRG\Gateway::initiate($url);
            $I->fail("Expected exception \SRG\Exception not thrown");
        } catch(\SRG\Exception $e) {
            $I->assertRegExp($message, $e->getMessage());
        }

        $url = 'https://wendig.io/my/doc.xml?version=2#beta';
        try {
            \SRG\Gateway::initiate($url);
            $I->fail("Expected exception \SRG\Exception not thrown");
        } catch(\SRG\Exception $e) {
            $I->assertRegExp($message, $e->getMessage());
        }

        $url = 'https://wendig.io/my/doc.xml';
        \SRG\Gateway::initiate($url);
    }
}