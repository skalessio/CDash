<?php
/*=========================================================================
  Program:   CDash - Cross-Platform Dashboard System
  Module:    $Id$
  Language:  PHP
  Date:      $Date$
  Version:   $Revision$

  Copyright (c) Kitware, Inc. All rights reserved.
  See LICENSE or http://www.cdash.org/licensing/ for details.

  This software is distributed WITHOUT ANY WARRANTY; without even
  the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
  PURPOSE. See the above copyright notices for more information.
=========================================================================*/
namespace CDash\Middleware\Oauth2Provider;

use CDash\Config;
use CDash\Controller\Auth\Session;
use CDash\Middleware\OAuth2Provider;
use League\OAuth2\Client\Provider\AbstractProvider;
use Omines\OAuth2\Client\Provider\Gitlab;

class GitLabProvider extends OAuth2Provider
{
    private $NameParts;

    public function __construct(Session $session, Config $config)
    {
        parent::__construct($session, $config);
        $this->AuthorizationOptions = ['scope' => ['read_user']];
        $this->NameParts = null;
    }

    public function getEmail()
    {
        $this->loadOwnerDetails();
        return strtolower($this->OwnerDetails->getEmail());
    }

    private function loadNameParts()
    {
        $this->loadOwnerDetails();
        $name = $this->OwnerDetails->getName();
        $this->NameParts = explode(' ', $name);
    }

    public function getFirstName()
    {
        $this->loadNameParts();
        return $this->NameParts[0];
    }

    public function getLastName()
    {
        $this->loadNameParts();
        return $this->NameParts[1];
    }

    /**
     * @return AbstractProvider
     */
    public function getProvider()
    {
        if (!$this->Provider) {
            $settings = $this->Config->get('OAUTH2_PROVIDERS');
            if (array_key_exists('GitLab', $settings)) {
                $gitlab_settings = $settings['GitLab'];
                if (array_key_exists('clientId', $gitlab_settings) &&
                    array_key_exists('clientSecret', $gitlab_settings) &&
                    array_key_exists('redirectUri', $gitlab_settings)) {
                    $this->Provider = new Gitlab($gitlab_settings);
                    $this->Valid = true;
                }
            }
        }
        return $this->Provider;
    }
}
