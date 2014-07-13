<?php

require(__DIR__ . '/../assets/PDOMock.php');

class OAuthModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @backupGlobals disabled
     * @backupStaticAttributes disabled
     */
    public function testConstructorSetsCorrectly()
    {
        $oAuthModel = $this->getOAuthModel();
        $this->assertEquals('base', $oAuthModel->getBase());
        $this->assertEquals('version', $oAuthModel->getVersion());
        $this->assertInstanceOf('PDOMock', $oAuthModel->getDb());
    }

    public function testGetConsumerNameWithApplication()
    {
        $oAuthModel = $this->getOAuthModel();

        $sql = 'select at.consumer_key, c.id, c.application '
            . 'from oauth_access_tokens at '
            . 'left join oauth_consumers c using (consumer_key) '
            . 'where at.access_token=:access_token ';
        $token = md5(microtime(true));

        $result = ['application' => 'someApplication'];

        $statementMock = $this->getMockBuilder('PDOStatement')
            ->setMethods(['execute', 'fetch'])
            ->getMock();

        $statementMock
            ->expects($this->once())
            ->method('execute')
            ->with(array("access_token" => $token))
            ->will($this->returnValue(true));

        $statementMock
            ->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($result));

        $oAuthModel->getDb()
            ->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->will($this->returnValue($statementMock));

        $this->assertEquals('someApplication', $oAuthModel->getConsumerName($token));
    }

    public function testGetConsumerNameWithOutApplication()
    {
        $oAuthModel = $this->getOAuthModel();

        $sql = 'select at.consumer_key, c.id, c.application '
            . 'from oauth_access_tokens at '
            . 'left join oauth_consumers c using (consumer_key) '
            . 'where at.access_token=:access_token ';
        $token = md5(microtime(true));

        $result = ['application' => ''];

        $statementMock = $this->getMockBuilder('PDOStatement')
            ->setMethods(['execute', 'fetch'])
            ->getMock();

        $statementMock
            ->expects($this->once())
            ->method('execute')
            ->with(array("access_token" => $token))
            ->will($this->returnValue(true));

        $statementMock
            ->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($result));

        $oAuthModel->getDb()
            ->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->will($this->returnValue($statementMock));

        $this->assertEquals('joind.in', $oAuthModel->getConsumerName($token));
    }

    public function testVerifyAccessToken()
    {
        $oAuthModel = $this->getOAuthModel();

        $sql = 'select id, user_id from oauth_access_tokens'
            . ' where access_token=:access_token';

        $update_sql = 'update oauth_access_tokens '
            . ' set last_used_date = NOW()'
            . ' where id = :id';

        $token = md5(microtime(true));

        $result = [
            'id' => 13,
            'user_id' => 42,
        ];

        $statementMock = $this->getMockBuilder('PDOStatement')
            ->setMethods(['execute', 'fetch'])
            ->getMock();

        $statementMock
            ->expects($this->once())
            ->method('execute')
            ->with(array("access_token" => $token))
            ->will($this->returnValue(true));

        $statementMock
            ->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($result));

        $statement2Mock = $this->getMockBuilder('PDOStatement')
            ->setMethods(['execute'])
            ->getMock();

        $statement2Mock
            ->expects($this->once())
            ->method('execute')
            ->with(['id' => $result['id']])
            ->will($this->returnValue(true));

        $oAuthModel->getDb()
            ->expects($this->at(0))
            ->method('prepare')
            ->with($sql)
            ->will($this->returnValue($statementMock));

        $oAuthModel->getDb()
            ->expects($this->at(1))
            ->method('prepare')
            ->with($update_sql)
            ->will($this->returnValue($statement2Mock));

        $this->assertEquals($oAuthModel->verifyAccessToken($token), $result['user_id']);
    }

    private function getOAuthModel()
    {
        /** @var PDO $db */
        $db = $this->getMock('PDOMock');
        /** @var Request $request */
        $request = $this->getMockBuilder('Request')
            ->disableOriginalConstructor(true)
            ->getMock();
        $request->base = 'base';
        $request->version = 'version';

        $oAuthModel = new OAuthModel($db, $request);

        return $oAuthModel;
    }
}
 