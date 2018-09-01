<?php

use auth\SSO;
use auth\SSOCache;
use spitfire\core\Environment;
use spitfire\exceptions\PublicException;
use spitfire\io\session\Session;

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

class AuthenticatedController extends BaseController
{
	
	const AUTH_NONE = 0x0;
	const AUTH_USER = 0x1;
	const AUTH_APP  = 0x2;
	const AUTH_INT  = 0x4;
	
	protected $_auth;
	
	protected $_source;
	protected $_user;
	protected $_app;
	
	/**
	 *
	 * @var SSO
	 */
	protected $sso;
	
	public function _onload() {
		/*
		 * First, we call the parent mechanism to ensure that the server is properly
		 * intialized.
		 */
		parent::_onload();
		
		/*
		 * Check whether the server can provide authentication for remote applications
		 */
		if (Environment::get('SSO')) {
			$this->sso = new SSOCache(Environment::get('SSO'));
		}
		else {
			//TODO: Replace with fallback "proxy" that authenticates this server against
			//a sibling inside the network and pipes it to the SSO server
			throw new PublicException('SSO is not properly configured', 500);
		}
		
		/*
		 * Check whether the application is being authorized by a server inside the
		 * network.
		 */
		if ($this->request->isPost() && isset($_POST['source'])) {
			
			/*
			 * We set the post variable to the actual body of the message, basically 
			 * discarding the envelope.
			 */
			$msg    = $this->keys->unpack($_POST);
			$source = $_POST['source'];
			$_POST  = $msg;
			
			$this->_auth = self::AUTH_INT;
			$this->_source = $source;
		}
		
		/**
		 * The remote application is an application that is not cloudy but connected
		 * to the same SSO server.
		 * 
		 * @todo This requires a mechanism to fallback from the SSO object in case
		 * the server is not the leader and therefore has no privileges to authenticate
		 * the user.
		 */
		elseif (isset($_GET['signature'])) {
			$this->_app = $this->sso->authApp($_GET['signature']);
			$this->_auth = $this->_app->getAuthenticated()? self::AUTH_APP : self::AUTH_NONE;
		}
		
		/**
		 * The client is a user trying to manage the server.
		 * 
		 * @todo This requires a mechanism to fallback from the SSO object in case
		 * the server is not the leader and therefore has no privileges to authenticate
		 * the user.
		 */
		else {
			$session   = Session::getInstance();
			
			$token = isset($_GET['token'])? $this->sso->makeToken($_GET['token']) : $session->getUser();
			$this->_user = $token? $token->getTokenInfo() : null;
			
			$this->_auth = $this->_user? self::AUTH_USER : self::AUTH_NONE;
		}
	}
	
}
