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

class BucketController extends AuthenticatedController
{
	
	public function all() {
		//TODO: Implement
	}
	
	/**
	 * 
	 * @validate >> POST#name(string required)
	 * @validate >> POST#replicas(positive number required)
	 * @throws PublicException
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
			throw new PublicException('This server cannot accept new buckets', 403);
		}
		
		try {
			if (!$this->request->isPost()) { throw new HTTPMethodException('Not Posted'); }
			if (!$this->validation->isEmpty()) { throw new ValidationException('Validation failed', 0, $this->validate->toArray()); }
			
			$record = db()->table('bucket')->newRecord();
			$record->uniqid = uniqid();
			$record->name = $_POST['name'];
			$record->replicas = $_POST['replicas'];
			$record->cluster = db()->table('cluster')->get('_id', $_POST['cluster'])->first(true);
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
	
	public function read($uniqid) {
		
		$bucket = db()->table('bucket')->get('uniqid', $uniqid)->first(true);
		
		/*
		 * If the client consuming this endpoint has provided no authentication at
		 * all, the server will immediately reject the request.
		 */
		if ($this->_auth === AuthenticatedController::AUTH_NONE) {
			throw new PublicException('Authentication is required to access this endpoint', 403);
		}
		
		/*
		 * If it is an application, we need to make sure that the application was 
		 * granted r/w access on the data in the first place.
		 */
		elseif ($this->_auth === AuthenticatedController::AUTH_APP) {
			$grant = $this->sso->authApp($_GET['signature'], null, ['bucket.' . $bucket->uniqid]);
			
			if (!$grant->getContext('bucket.' . $bucket->uniqid)->exists()) {
				$grant->getContext('bucket.' . $bucket->uniqid)->create(sprintf('Bucket %s (%s)', $bucket->name, $bucket->uniqid), 'Allows for read / write access to the bucket');
			}
			
			if (!$grant->getContext('bucket.' . $bucket->uniqid)->isGranted()) {
				throw new PublicException('Context level insufficient.', 403);
			}
		}
		
		$this->view->set('bucket', $bucket);
		$this->view->set('self', $this->settings->read('uniqid'));
		$this->view->set('keys', $this->keys);
		
	}
	
	public function update(BucketModel$bucket) {
		//TODO: Implement
	}
	
	public function delete(BucketModel$bucket) {
		//TODO: Implement
	}
	
}
