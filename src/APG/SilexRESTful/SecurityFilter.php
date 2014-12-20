<?php

namespace APG\SilexRESTful;

use Symfony\Component\Security\Core\SecurityContext;

class SecurityFilter {

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
    public function isMethodAllowedForItemName($methodName, $itemName) {
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