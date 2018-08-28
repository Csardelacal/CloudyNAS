<?php

use auth\SSOCache;
use cloudy\helper\KeyHelper;
use cloudy\helper\SettingsHelper;
use spitfire\core\Environment;

/* 
 * The MIT License
 *
 * Copyright 2018 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class BaseController extends Controller
{
	
	/**
	 *
	 * @var SettingsHelper
	 */
	protected $settings;
	
	/**
	 *
	 * @var \auth\SSO
	 */
	protected $sso;
	
	protected $user;
	
	/**
	 *
	 * @var KeyHelper
	 */
	protected $keys;
	
	/**
	 * Whenever the base controller is summoned, it will ensure that certain basic
	 * requirements are available.
	 */
	public function _onload() {
		
		/*
		 * Prepare a settings helper. This class will allow the application to 
		 * read and write the settings for the application.
		 */
		$this->settings = new SettingsHelper(db()->setting);
		
		/*
		 * Check if the server is configured. Otherwise, start the configuration
		 * of the basic server settings.
		 */
		if (!$this->settings->read('privkey')) {
			/*
			 * All the servers can sign their requests to each other with a public
			 * key. If this server doesn't happen to have one, it will generate one
			 * to ensure that the communication between servers is not compromised.
			 */
			$keygen = new KeyHelper(db());
			list($private, $public) = $keygen->generate();
			
			$this->settings->set('uniqid',  uniqid());
			$this->settings->set('privkey', $private);
			$this->settings->set('pubkey',  $public);
			
		}
		else {
			$this->keys = new KeyHelper(db(), $this->settings->read('uniqid'), $this->settings->read('pubkey'), $this->settings->read('privkey'));
		}
		
		/*
		 * Check whether the server can provide authentication for remote applications
		 */
		if (Environment::get('SSO')) {
			$this->sso = new SSOCache(Environment::get('SSO'));
			$session   = \spitfire\io\session\Session::getInstance();
			
			$token = isset($_GET['token'])? $this->sso->makeToken($_GET['token']) : $session->getUser();
			$this->user = $token? $token->getTokenInfo() : null;
		}
		else {
			throw new \spitfire\exceptions\PublicException('SSO is not properly configured', 500);
		}
	}
	
}