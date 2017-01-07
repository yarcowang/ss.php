<?php
/**
 * Proj. ss.php
 *
 * @author Yarco Wang <yarco.wang@gmail.com>
 * @since 17/1/7 下午3:30
 */
use Yarco\SimpleService\SimpleService;

class SimpleServiceTest extends PHPUnit_Framework_TestCase
{
    public function testDescription()
    {
        $ss = new SimpleService();
        $ss->asSimpleService('demo', 'https://demo/');
        $this->assertEquals(
            ['get' => ['developer']],
            $ss->setDescription('_debug', [
                'get' => ['developer']
            ])->getDescription('_debug')
        );
    }

    public function testCreateFromFile()
    {
        $ss = SimpleService::fromUrl(__DIR__ . '/fixtures/test.json');

        $this->assertEquals('test', $ss->name);
        $this->assertEquals('https://demo/', $ss->baseUrl);
        $this->assertEquals(["guest", "admin", "editor"], $ss->roles);
    }

    public function testGetRoles()
    {
        $ss = SimpleService::fromUrl(__DIR__ . '/fixtures/test.json');

        $this->assertEquals(
            ['guest', 'admin', 'editor'],
            $ss->getRoles()
        );
        $this->assertEquals(
            ['admin', 'editor'],
            $ss->getRoles(true)
        );
        $this->assertEquals(
            ['test.guest', 'test.admin', 'test.editor'],
            $ss->getRoles(false, true)
        );
        $this->assertEquals(
            ['test.admin'],
            $ss->getRoles(true, true, ['post'])
        );
    }

    public function testToJson()
    {
        $ss = new SimpleService();
        $ss->asSimpleService('demo', 'https://demo');
        $ss->setDescription('_api', [
            'get' => ['admin'] // only admin can visit(get) https://demo/_api
        ]);

        $this->assertEquals(
            '{"name":"demo","baseUrl":"https:\/\/demo\/","roles":[],"apis":{"_api":{"get":["admin"]}}}',
            $ss->toJson()
        );
    }

    public function testCanDo()
    {
        $ss = SimpleService::fromUrl(__DIR__ . '/fixtures/test.json');

        $this->assertFalse($ss->can('admin', 'get', '_nothing'));
        $this->assertFalse($ss->can('guest', 'get', 'api/user/1')); // guest must be set explicit
        $ss->setDescription('api/user/1', [
            'get' => ['*', 'guest']
        ]);
        $this->assertTrue($ss->can('guest', 'get', 'api/user/1')); // now, it should be OK
        $this->assertTrue($ss->can('editor', 'get', 'api/user/1')); // because '*' perms is set
        $this->assertFalse($ss->can('editor', 'get', 'api/admin/1')); // no, only admin can do this
        $this->assertTrue($ss->can('admin', 'post', 'api/admin/1')); // of cause
    }


}
