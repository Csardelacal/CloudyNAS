<?php

use cloudy\Role;
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

class LinkController extends AuthenticatedController
{
	
	public function all() {
		//TODO: Implement
	}
	
	public function create() {
		$self = db()->table('server')->get('uniqid', $this->settings->read('uniqid'))->first();
		
		/*
		 * Only leaders are allowed to accept requests to create links.
		 */
		if (!($self->role & Role::ROLE_MASTER)) {
			throw new PublicException('This server cannot accept new links', 403);
		}
		
		if (isset($_POST['media']) && !empty($_POST['media'])) {
			$media = db()->table('media')->get('uniqid', $_POST['media'])->first(true);
			$bucket = $media->bucket;
		}
		else {
			$bucket = db()->table('bucket')->get('uniqid', $_POST['bucket'])->first(true);
			$media  = db()->table('media')->get('name', $_POST['name'])->where('bucket', $bucket)->first(true);
		}
		
		if ($this->_auth === AuthenticatedController::AUTH_NONE) {
			throw new PublicException('Authentication required', 403);
		}
		elseif ($this->_auth === AuthenticatedController::AUTH_APP) {
			$grant = $this->sso->authApp($_GET['signature'], null, ['bucket.' . $bucket->uniqid]);
			
			if (!$grant->getContext('bucket.' . $bucket->uniqid)->exists()) {
				$grant->getContext('bucket.' . $bucket->uniqid)->create(sprintf('Bucket %s (%s)', $bucket->name, $bucket->uniqid), 'Allows for read / write access to the bucket');
			}
			
			if (!$grant->getContext('bucket.' . $bucket->uniqid)->isGranted()) {
				throw new PublicException('Context level insufficient.', 403);
			}
		}
		
		
		$link = db()->table('link')->newRecord();
		$link->media = $media;
		$link->expires = isset($_POST['ttl'])? time() + $_POST['ttl'] : null;
		$link->store();
		
		$this->view->set('link', $link);
	}
	
	public function read($uniqid, $revid = null) {
		
		if(!in_array($this->_auth, [AuthenticatedController::AUTH_INT, AuthenticatedController::AUTH_APP])) {
			throw new PublicException('Insufficient permissions', 401);
		}
		
		$link = db()->table('link')->get('uniqid', $uniqid)->first(true);
		
		if (!$revid) {
			$revision = db()->table('revision')->get('media', $link->media)->where('expires', null)->first();
			$files    = db()->table('file')->get('revision', $revision)->all();
		}
		else {
			$revision = db()->table('revision')->get('media', $link->media)->where('uniqid', $revid)->first();
			$files    = db()->table('file')->get('revision', $revision)->all();
		}
		
		$this->view->set('link',  $link);
		$this->view->set('files', $files);
	}
	
	public function update(ClusterModel$cluster) {
		//TODO: Implement
	}
	
	public function delete(ClusterModel$cluster) {
		//TODO: Implement
	}
	
}
