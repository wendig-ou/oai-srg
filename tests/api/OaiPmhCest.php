<?php


class OaiPmhCest
{
    public function _before(ApiTester $I)
    {
    }

    public function _after(ApiTester $I)
    {
    }

    // tests
    public function testOAI(ApiTester $I)
    {
        $url = 'http://localhost:3002/repository.01.xml';
        $I->sendGet('/oai-pmh/localhost:3002/repository.01.xml', ['verb' => 'Identify']);
        $I->seeResponseCodeIs(404);

        \SRG\Gateway::initiate($url);

        $I->sendGet('/oai-pmh/localhost:3002/repository.01.xml', ['verb' => 'Identify']);
        $I->seeResponseCodeIs(503);

        \SRG\Gateway::approve($url);

        $I->sendGet('/oai-pmh/localhost:3002/repository.01.xml', []);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('badVerb');

        $I->sendGet('/oai-pmh/localhost:3002/repository.01.xml', ['verb' => 'Wrong']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('badVerb');

        $I->sendGet('/oai-pmh/localhost:3002/repository.01.xml', ['verb' => 'Identify']);
        $I->seeResponseCodeIs(200);

        $I->sendGet('/oai-pmh/localhost:3002/repository.01.xml', ['verb' => 'ListMetadataFormats']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('<metadataPrefix>oai_dc</metadataPrefix>');
        $I->seeResponseContains('<metadataPrefix>oai_rfc1807</metadataPrefix>');

        $I->sendGet('/oai-pmh/localhost:3002/repository.01.xml', ['verb' => 'GetRecord']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('badArgument');
        $I->seeResponseContains('No identifier given');

        $I->sendGet('/oai-pmh/localhost:3002/repository.01.xml', [
            'verb' => 'GetRecord',
            'identifier' => 'oai:arXiv:cs/0112017'
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('badArgument');
        $I->seeResponseContains('No metadata prefix given');

        $I->sendGet('/oai-pmh/localhost:3002/repository.01.xml', [
            'verb' => 'GetRecord',
            'identifier' => 'doesnt-exist',
            'metadataPrefix' => 'oai_dc'
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('idDoesNotExist');
        $I->seeResponseContains('record not found');

        $I->sendGet('/oai-pmh/localhost:3002/repository.01.xml', [
            'verb' => 'GetRecord',
            'identifier' => 'oai:arXiv:cs/0112017',
            'metadataPrefix' => 'oai_dc'
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('Using Structural Metadata to Localize Experience');

        $I->sendGet('/oai-pmh/localhost:3002/repository.01.xml', ['verb' => 'ListIdentifiers']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('badArgument');
        $I->seeResponseContains('No metadata prefix given');

        $I->sendGet('/oai-pmh/localhost:3002/repository.01.xml', [
            'verb' => 'ListIdentifiers',
            'metadataPrefix' => 'oai_dc'
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('oai:arXiv:cs/0112017');
        $I->seeResponseContains('oai:perseus:Perseus:text:1999.02.0084');
        $I->dontSeeResponseContains('123456');

        $body = $I->grabResponse();
        $doc = new DomDocument();
        $doc->loadXML($body);
        $ns = 'http://www.openarchives.org/OAI/2.0/';
        $token = $doc->getElementsByTagNameNS($ns, 'resumptionToken')->item(0)->nodeValue;
        $I->sendGet('/oai-pmh/localhost:3002/repository.01.xml', [
            'verb' => 'ListIdentifiers',
            'resumptionToken' => trim($token)
        ]);
        $I->seeResponseCodeIs(200);
        $I->dontSeeResponseContains('oai:perseus:Perseus:text:1999.02.0084');
        $I->dontSeeResponseContains('oai:arXiv:cs/0112017');
        $I->seeResponseContains('123456');
    }
}
