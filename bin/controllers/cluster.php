<?php

use cloudy\Role;
use spitfire\exceptions\HTTPMethodException;
use spitfire\exceptions\PublicException;
use spitfire\validation\ValidationException;

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

class ClusterController extends AuthenticatedController
{
	
	public function all() {
		//TODO: Implement
	}
	
	/**
	 * 
	 * @validate >> POST#name(string required)
	 * @throws PublicException
	 * @throws HTTPMethodException
	 * @throws ValidationException
	 */
	public function create() {
		/*
		 * Buckets can only be created by humans and authorized third parties. There's 
		 * no need for our application to allow other stuff to happen
		 */
		if ($this->_auth === AuthenticatedController::AUTH_USER) {
			#The user is authenticated, let him continue
		}
		
		elseif ($this->_auth === AuthenticatedController::AUTH_APP) {
			throw new PublicException('Not implemented', 502);
		}
		
		else {
			throw new PublicException('Unauthorized', 403);
		}
		
		$self = db()->table('server')->get('uniqid', $this->settings->read('uniqid'))->first();
		
		/*
		 * Only leaders are allowed to accept requests to create buckets.
		 */
		if (!($self->role & Role::ROLE_LEADER)) {
			throw new PublicException('This server cannot accept new clusters', 403);
		}
		
		try {
			if (!$this->request->isPost()) { throw new HTTPMethodException('Not Posted'); }
			if (!$this->validation->isEmpty()) { throw new ValidationException('Validation failed', 0, $this->validate->toArray()); }
			
			$record = db()->table('cluster')->newRecord();
			$record->uniqid = uniqid();
			$record->name = $_POST['name'];
			$record->store();
			
			$this->view->set('result', $record);
		} 
		catch (HTTPMethodException $ex) {
			#Do nothing, just show the form
		}
		catch (ValidationException $ex) {
			$this->view->set('errors', $ex->getResult());
		}
	}
	
	public function read(ClusterModel$cluster) {
		
		/*
		 * Buckets can only be created by humans and authorized third parties. There's 
		 * no need for our application to allow other stuff to happen
		 */
		if ($this->_auth === AuthenticatedController::AUTH_USER || $this->_auth === AuthenticatedController::AUTH_APP) {
			#The user is authenticated, let him continue
		}
		
		else {
			throw new PublicException('Unauthorized', 403);
		}
		
		$this->view->set('cluster', $cluster);
	}
	
	public function update(ClusterModel$cluster) {
		//TODO: Implement
	}
	
	public function delete(ClusterModel$cluster) {
		//TODO: Implement
	}
	
}
