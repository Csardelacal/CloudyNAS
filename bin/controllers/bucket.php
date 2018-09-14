<?php

use spitfire\exceptions\PublicException;

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
	
	public function create() {
		//TODO: Implement
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
	}
	
	public function update(BucketModel$bucket) {
		//TODO: Implement
	}
	
	public function delete(BucketModel$bucket) {
		//TODO: Implement
	}
	
}
