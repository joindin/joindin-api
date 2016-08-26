<?php

/**
 *
 */
class ApiMapperTest extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->pdo     = new PDO('sqlite:memory:');
        $this->request = $this->createMock('Request');
    }

    public function testThatMapperInstanceHasDependencies()
    {
        $mapper = new ApiMapper($this->pdo, $this->request);

        $this->assertAttributeEquals($this->pdo, '_db', $mapper);
        $this->assertAttributeEquals($this->request, '_request', $mapper);
    }
}
