<?php
/**
 * Proj. ss.php
 *
 * @author Yarco Wang <yarco.wang@gmail.com>
 * @since 17/1/7 下午2:22
 */

namespace Yarco\SimpleService;


trait SimpleServiceTrait
{
    /**
     * @var string default anonymous role
     */
    public static $Anonymous = 'guest';

    public $name;
    public $baseUrl;
    public $roles = [];

    protected $_data = [];

    /**
     * @param string $url
     * @return bool|SimpleService
     */
    public static function fromUrl(string $url)
    {
        $c = file_get_contents($url);
        if (!$c) {
            return false;
        }

        $c = json_decode($c, true);
        $the = new static();
        $the->asSimpleService($c['name'] ?? 'demo', $c['baseUrl'] ?? dirname($url), $c['roles'] ?? []);

        if (isset($c['apis'])) {
            foreach($c['apis'] as $api => $perms) {
                $the->setDescription($api, $perms);
            }
        }

        return $the;
    }

    /**
     * @param string $name the name of the service
     * @param string $baseUrl base url which will be
     * @param array $roles declared roles
     */
    public function asSimpleService(string $name, string $baseUrl, array $roles = [])
    {
        $this->name = $name;
        $this->baseUrl = $baseUrl[strlen($baseUrl) - 1] === '/' ? $baseUrl : $baseUrl . '/';
        $this->roles = $roles;
    }

    /**
     * @param string $api
     * @param array $desc
     * @return $this
     */
    public function setDescription(string $api, array $desc = [])
    {
        $this->_data[$api] = $desc;
        return $this;
    }

    /**
     * @param string $api
     * @return array
     */
    public function getDescription(string $api)
    {
        return $this->_data[$api] ?? [];
    }

    /**
     * @param bool $force force it to get roles from api definitions
     * @param bool $prefix
     * @param array $without ignored keys
     * @return array
     */
    public function getRoles($force = false, $prefix = false, array $without = [])
    {
        $force ? $roles = [] : $roles = & $this->roles;

        if (empty($roles)) {
            // parse from apis
            foreach($this->_data as $api => $perms) {
                foreach($perms as $k => $rs) {
                    if (in_array($k, $without)) {
                        continue;
                    }
                    $rs = array_filter($rs, function($r) { return $r !== '*'; });
                    $roles = array_merge($roles, $rs);
                }
            }
        }
        $roles = array_unique($roles);

        return $prefix ? array_map(function($i) {
            return $this->name . '.' . $i;
        }, $roles) : $roles;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode([
            'name' => $this->name,
            'baseUrl' => $this->baseUrl,
            'roles' => $this->roles,
            'apis' => $this->_data
        ]);
    }

    /**
     * @param string $role
     * @param string $action
     * @param string $api
     * @return bool
     */
    public function can(string $role, string $action, string $api)
    {
        if (!isset($this->_data[$api]) || !isset($this->_data[$api][strtolower($action)])) {
            return false;
        }

        $roles = $this->_data[$api][strtolower($action)];
        if ($role === static::$Anonymous && !in_array($role, $roles)) {
            return false;
        }

        if ($role !== static::$Anonymous) {
            return in_array($role, $roles) || in_array('*', $roles) ? true : false;
        }

        return true;
    }
}