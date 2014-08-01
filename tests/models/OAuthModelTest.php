<?php

require(__DIR__ . '/../assets/PDOMock.php');

class OAuthModelTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var OAuthModel
     */
    protected $oAuthModel;

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

    private function getOAuthModel()
    {
        if (!$this->oAuthModel) {
            /** @var PDO $db */
            $db = $this->getMock('PDOMock');
            /** @var Request $request */
            $request = $this->getMockBuilder('Request')
                ->disableOriginalConstructor(true)
                ->getMock();
            $request->base = 'base';
            $request->version = 'version';

            $this->oAuthModel = new OAuthModel($db, $request);
        }
        return $this->oAuthModel;
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

    public function testCreateAccessTokenFromPasswordWithNoUserIdReturnsFalse()
    {

        $clientId = 42;
        $username = 'marvin';
        $password = 'paranoid!android';
        $userId = null;

        $oAuthModel = $this->getOAuthModel();

        // add mocking for get user id
        $this->addGetUserId($username, $password, $userId);

        $this->assertFalse($oAuthModel->createAccessTokenFromPassword($clientId, $username, $password));

    }

    private function addGetUserId($username, $password, $userId, $badResult = false)
    {
        $sql = 'SELECT ID, email FROM user
                WHERE username=:username AND password=:password';

        $result = ['ID' => $userId];

        if ($badResult) {
            $result = false;
        }

        $oAuthModel = $this->getOAuthModel();

        $statementMock = $this->getMockBuilder('PDOStatement')
            ->setMethods(['execute', 'fetch'])
            ->getMock();

        $statementMock
            ->expects($this->once())
            ->method('execute')
            ->with(["username" => $username, "password" => md5($password)]);

        $statementMock
            ->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($result));

        $oAuthModel->getDb()
            ->expects($this->at(0))
            ->method('prepare')
            ->with($sql)
            ->will($this->returnValue($statementMock));
    }

    public function testCreateAccessTokenFromPasswordWithUserId()
    {

        $clientId = 42;
        $username = 'marvin';
        $password = 'paranoid!android';
        $userId = 22;
        $accessToken = 'tokentokentokenytoken';

        $oAuthModel = $this->getOAuthModel();

        // add mocking for get user id
        $this->addGetUserId($username, $password, $userId);
        // add mocking for new access token
        $this->addNewAccessToken(true);

        $this->assertInternalType(
            'array',
            $oAuthModel->createAccessTokenFromPassword($clientId, $username, $password)
        );

    }

    private function addNewAccessToken($result = true)
    {

        $oAuthModel = $this->getOAuthModel();

        $sql = "INSERT INTO oauth_access_tokens set
                access_token = :access_token,
                access_token_secret = :access_token_secret,
                consumer_key = :consumer_key,
                user_id = :user_id,
                last_used_date = NOW()
                ";

        $statementMock = $this->getMock('PDOStatement');

        if($result) {
            $statementMock->expects($this->once())
                ->method('execute')
                ->will($this->returnValue(true));
        }

        $oAuthModel->getDb()
            ->expects($this->at(1))
            ->method('prepare')
            ->with($sql)
            ->will($this->returnValue($statementMock));

    }

    public function testExpireOldTokens()
    {

        $clientIds = [
            42,
            84,
            126
        ];

        $sql = "DELETE FROM oauth_access_tokens WHERE
                    consumer_key=:consumer_key AND last_used_date < :expiry_date";

        $oAuthModel = $this->getOAuthModel();

        $statementMock = $this->getMockBuilder('PDOStatement')
            ->setMethods(['execute'])
            ->getMock();

        $statementMock
            ->expects($this->exactly(3))
            ->method('execute');

        $oAuthModel->getDb()
            ->expects($this->exactly(3))
            ->method('prepare')
            ->with($sql)
            ->will($this->returnValue($statementMock));

        $this->assertNull($oAuthModel->expireOldTokens($clientIds));

    }


    public function testGetUserIdWithNoIdReturnsResult()
    {
        $oAuthModel = $this->getOAuthModel();
        $this->addGetUserId('username', 'password', null, true);
        $this->assertFalse($oAuthModel->createAccessTokenFromPassword(22, 'username', 'password'));
    }

    public function testGetUserIdWithResultReturnsResult()
    {


        $clientId = 42;
        $username = 'marvin';
        $password = 'paranoid!android';
        $userId = 22;
        $accessToken = 'tokentokentokenytoken';

        $oAuthModel = $this->getOAuthModel();

        // add mocking for get user id
        $this->addGetUserId($username, $password, $userId);
        // add mocking for new access token
        $this->addNewAccessToken(false);

        $this->assertInternalType(
            'array',
            $oAuthModel->createAccessTokenFromPassword($clientId, $username, $password)
        );

    }
}
 