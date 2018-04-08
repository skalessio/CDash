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
use League\OAuth2\Client\Provider\Github;

require_once dirname(__DIR__) . '/config/config.php';
require_once 'auth/OAuth2Provider.php';

class GitHubProvider extends OAuth2Provider
{
    private $NameParts;

    public function __construct(Session $session, Config $config)
    {
        parent::__construct($session, $config);

        $this->AuthorizationOptions = ['scope' => ['read:user', 'user:email']];
        $this->NameParts = null;

        if (array_key_exists('GitHub', $this->OAuth2Settings)) {
            $github_settings = $this->OAuth2Settings['GitHub'];
            if (array_key_exists('clientId', $github_settings) &&
                    array_key_exists('clientSecret', $github_settings) &&
                    array_key_exists('redirectUri', $github_settings)) {
                $this->Provider = new Github($github_settings);
                $this->Valid = true;
            }
        }
    }

    public function getEmail()
    {
        $request = $this->Provider->getAuthenticatedRequest(
                'GET',
                'https://api.github.com/user/public_emails',
                $this->Token
                );
        $emails = json_decode(
                $this->Provider->getResponse($request)->getBody());
        $email = '';
        foreach ($emails as $e) {
            if ($e->primary) {
                $email = $e->email;
                break;
            }
        }
        return strtolower($email);
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
        // TODO: Implement getProvider() method.
    }
}
