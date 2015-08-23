<?php

class TalkSpeakerMapperTest extends PHPUnit_Extensions_Database_TestCase
{
    protected $pdo = null;

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        if (null === $this->pdo) {
            $this->pdo = new PDO('sqlite::memory:');
            $this->pdo->exec('create table talk_speaker(talk_id int, speaker_name, ID int, speaker_id int, status)');
            $this->pdo->exec('create table user(username, password, email, lastlogin int, ID int, admin int, full_name, active int, twitter_username, request_code)');

        }
        return $this->createDefaultDBConnection($this->pdo, ':memory:');
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createXMLDataSet(__DIR__ . '/_files/talk_user_seed.xml');
    }

    public function testGetUsers()
    {
        $request = new Request([],[]);
        $talkSpeakerMapper = new TalkSpeakerMapper($this->getConnection()->getConnection(), $request);

        $this->assertEquals([
            'speakers' => [
                [
                    'uri' => 'http:////speakers/1',
                    'verbose_uri' => 'http:////speakers/1?verbose=yes',
                    'talk_uri' => 'http:////talks/44',
                    'user_uri' => 'http:////users/12',
                ],
                [
                    'uri' => 'http:////speakers/2',
                    'verbose_uri' => 'http:////speakers/2?verbose=yes',
                    'talk_uri' => 'http:////talks/44',
                ],
                [
                    'uri' => 'http:////speakers/3',
                    'verbose_uri' => 'http:////speakers/3?verbose=yes',
                    'talk_uri' => 'http:////talks/44',
                    'user_uri' => 'http:////users/13',
                ],
                [
                    'uri' => 'http:////speakers/4',
                    'verbose_uri' => 'http:////speakers/4?verbose=yes',
                    'talk_uri' => 'http:////talks/44',
                    'user_uri' => 'http:////users/22'
                ],
            ],
            'meta' => [
                'count' => 4,
                'total' => 4,
                'this_page' => 'http://?start=0&resultsperpage=20',
            ],
        ], $talkSpeakerMapper->getSpeakersByTalkId(44, 10, 0));
    }

    public function testGettingUsersForNonexistentEvent()
    {
        $request = new Request([],[]);
        $talkSpeakerMapper = new TalkSpeakerMapper($this->getConnection()->getConnection(), $request);

        $this->assertfalse( $talkSpeakerMapper->getSpeakersByTalkId('', 10, 0));
    }
}