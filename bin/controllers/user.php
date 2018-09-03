<?php

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

class UserController extends AuthenticatedController
{
	
	public function login() {
		
		/*
		 * Check whether the user is already logged in, if this is the case, then
		 * it's safe to redirect the user back to the rest of the application.
		 */
		if ($this->_user) {
			return $this->response->setBody('Redirecting...')->getHeaders()->redirect(isset($_GET['returnto'])? $_GET['returnto'] : url());
		}
		
		$session = Session::getInstance();
		$sso     = $this->sso;
		
		$token   = $sso->createToken();
		$session->lock($token);
		
		if (!$token->isAuthenticated()) {
			return $this->response->setBody('Redirecting...')
				->getHeaders()->redirect($token->getRedirect(url('user', 'login', $_GET->getRaw())->absolute(), url('user', 'failure')->absolute()));
		}
		else {
			return $this->response->setBody('Redirecting...')->getHeaders()->redirect(isset($_GET['returnto'])? $_GET['returnto'] : url());
		}
		
	}
	
	public function failure() {
		
	}
	
}