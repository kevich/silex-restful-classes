<?php

namespace APG\SilexRESTful;

use Symfony\Component\Security\Core\SecurityContext;

class SecurityFilter
{

    /**
     * @var array
     */
    private $configuration;

    /**
     * @var SecurityContext
     */
    private $securityService;

    /**
     * @param array $configuration
     */
    public function __construct($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param SecurityContext $securityService
     */
    public function setSecurityService($securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * @param string $methodName
     * @param string $itemName
     * @return bool
     */
    public function isMethodAllowedForItemName($methodName, $itemName)
    {
        foreach ($this->getUserRoles() as $role) {
            if (isset($this->configuration[$role->getRole()])) {
                if (isset($this->configuration[$role->getRole()][$itemName])) {
                    if (in_array($methodName, $this->configuration[$role->getRole()][$itemName])) {
                        return true;
                    }
                } else {
                    if (in_array($methodName, $this->configuration[$role->getRole()]['allowedMethods'])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function isMethodAllowedForItem($methodName, $itemName, $item)
    {
        foreach ($this->getUserRoles() as $role) {
            $roleAccessConfig = $this->configuration[$role->getRole()] ?? [];
            $dataLimitsConfig = $roleAccessConfig["dataLimits"] ?? false;
            if ($dataLimitsConfig) {
                foreach ($dataLimitsConfig as $config) {
                    if (
                        is_array($config['tables'] ?? null)
                        && array_key_exists('validator', $config)
                        && array_key_exists('allowedMethods', $config)
                        && in_array($itemName, $config['tables'], true)
                    ) {
                        $name = $config['validator']['property'];
                        $value = $this->prepareFilterValue($config['validator']['value']);
                        return $value === $item->$name && in_array($methodName, $config['allowedMethods'], true);
                    }
                }
            }
        }

        return false;
    }

    public function extendFilters($itemName, $filters)
    {
        foreach ($this->getUserRoles() as $role) {
            if (isset($this->configuration[$role->getRole()])) {
                if (
                    isset($this->configuration[$role->getRole()]['filters'])
                    && is_array($this->configuration[$role->getRole()]['filters'])
                ) {
                    foreach ($this->configuration[$role->getRole()]['filters'] as $filterConfig) {
                        if (
                            array_key_exists('tables', $filterConfig)
                            && array_key_exists('filter', $filterConfig)
                            && is_array($filterConfig['tables'])
                            && in_array($itemName, $filterConfig['tables'])
                        ) {
                            $filterConfig['filter']['value'] = $this->prepareFilterValue($filterConfig['filter']['value']);
                            $filters[] = (object)$filterConfig['filter'];
                        }
                    }
                }
            }
        }
        return $filters;
    }

    public function getAvailableRoles()
    {
        $roles = array();
        foreach ($this->configuration as $role => $roleProp) {
            $roles[] = array_merge(
                array('id' => $role),
                array_intersect_key($roleProp, array('name' => 'bulk'))
            );
        }
        return $roles;
    }

    /**
     * @return \Symfony\Component\Security\Core\Role\RoleInterface[]
     */
    private function getUserRoles()
    {
        $token = $this->securityService->getToken();
        if (!is_null($token)) {
            return $token->getRoles();
        }
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function prepareFilterValue($value)
    {
        if (is_string($value) && strpos($value, '@user->') !== false) {
            $userParam = str_replace('@user->', '', $value);
            $value = $this->getUser()->$userParam;
        }
        return $value;
    }

    /**
     * @return mixed
     */
    private function getUser()
    {
        $token = $this->securityService->getToken();
        if (!is_null($token)) {
            return $token->getUser();
        }
    }

}
