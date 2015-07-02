<?php

namespace Fusio\Backend\Api\User;

use PSX\Test\ControllerDbTestCase;
use PSX\Test\Environment;

class CollectionTest extends ControllerDbTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(__DIR__ . '/../../../fixture.xml');
    }

    public function testGet()
    {
        $response = $this->sendRequest('http://127.0.0.1/backend/user', 'GET', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf'
        ));

        $body   = (string) $response->getBody();
        $expect = <<<'JSON'
{
    "totalItems": 3,
    "startIndex": 0,
    "entry": [
        {
            "id": 3,
            "status": 2,
            "name": "disabled",
            "date": "2015-02-27T19:59:15Z"
        },
        {
            "id": 2,
            "status": 0,
            "name": "consumer",
            "date": "2015-02-27T19:59:15Z"
        },
        {
            "id": 1,
            "status": 1,
            "name": "admin",
            "date": "2015-02-27T19:59:15Z"
        }
    ]
}
JSON;

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertJsonStringEqualsJsonString($expect, $body, $body);
    }

    public function testPost()
    {
        $response = $this->sendRequest('http://127.0.0.1/backend/user', 'POST', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf'
        ), json_encode([
            'status' => 0,
            'name'   => 'test',
            'scopes' => ['foo', 'bar'],
        ]));

        $body   = (string) $response->getBody();
        $body   = preg_replace('/[[:xdigit:]]{40}/', '[password]', $body);
        $expect = <<<'JSON'
{
    "success": true,
    "message": "User successful created",
    "password": "[password]"
}
JSON;

        $this->assertEquals(201, $response->getStatusCode(), $body);
        $this->assertJsonStringEqualsJsonString($expect, $body, $body);

        // check database
        $sql = Environment::getService('connection')->createQueryBuilder()
            ->select('id', 'status', 'name')
            ->from('fusio_user')
            ->orderBy('id', 'DESC')
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getSQL();

        $row = Environment::getService('connection')->fetchAssoc($sql);

        $this->assertEquals(4, $row['id']);
        $this->assertEquals(0, $row['status']);
        $this->assertEquals('test', $row['name']);

        $sql = Environment::getService('connection')->createQueryBuilder()
            ->select('id', 'userId', 'scopeId')
            ->from('fusio_user_scope')
            ->where('userId = :userId')
            ->orderBy('id', 'DESC')
            ->getSQL();

        $routes = Environment::getService('connection')->fetchAll($sql, ['userId' => 4]);

        $this->assertEquals([[
            'id'      => 4,
            'userId'  => 4,
            'scopeId' => 2,
        ], [
            'id'      => 3,
            'userId'  => 4,
            'scopeId' => 3,
        ]], $routes);
    }

    public function testPut()
    {
        $response = $this->sendRequest('http://127.0.0.1/backend/user', 'PUT', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf'
        ), json_encode([
            'foo' => 'bar',
        ]));

        $body = (string) $response->getBody();

        $this->assertEquals(405, $response->getStatusCode(), $body);
    }

    public function testDelete()
    {
        $response = $this->sendRequest('http://127.0.0.1/backend/user', 'DELETE', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf'
        ), json_encode([
            'foo' => 'bar',
        ]));

        $body = (string) $response->getBody();

        $this->assertEquals(405, $response->getStatusCode(), $body);
    }
}