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

class MediaController extends AuthenticatedController
{
	
	public function all($bid) {
		$bucket = db()->table('bucket')->get('uniqid', $bid)->first(true);
		
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
		
		$query = db()->table('media')->get('bucket', $bucket);
		$query->setOrder('created', 'DESC');
		
		if (isset($_GET['until'])) {
			$query->where('uniqid', '>', $_GET['until']);
		}
		
		$this->view->set('files', $query->range(0, 50));
		$this->view->set('bucket', $bucket);
	}
	
	public function create() {
		$bucket   = db()->table('bucket')->get('uniqid', $_POST['bucket'])->first(true);
		
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
		
		if (db()->table('media')->get('bucket', $bucket)->where('name', $_POST['name'])->group()->where('expires', '>', time())->where('expires', null)->endGroup()->first()) {
			throw new PublicException('File already exists. Please refer to the update() endpoint', 400);
		}
		
		if (empty($_POST['file'])) {
			throw new PublicException('No file sent', 400);
		}
		
		
		$media = db()->table('media')->newRecord();
		$media->bucket = $bucket;
		$media->name   = $_POST['name'];
		$media->store();
		
		$revision = db()->table('revision')->newRecord();
		$revision->media = $media;
		$revision->mime  = $_POST['mime'];
		$revision->checksum = md5_file($_POST['file']->store()->getPath());
		$revision->store();
		
		$master = db()->table('server')->get('uniqid', $this->settings->read('uniqid'))->first(true);
		
		$file = db()->table('file')->newRecord();
		$file->revision = $revision;
		$file->server   = $master;
		$file->file     = $_POST['file']->store()->uri();
		$file->mime     = $_POST['mime'];
		$file->checksum = $revision->checksum;
		$file->expires  = time() + 86400 * 7;
		$file->commited = 1;
		$file->store();
		
		$link = db()->table('link')->newRecord();
		$link->media = $media;
		$link->expires = null;
		$link->store();
		
		$task = $this->tasks->get(\cloudy\task\FileDistributeTask::class);
		$task->load($revision->uniqid);
		$this->tasks->send($master, $task);
		
		$this->view->set('media', $media);
		$this->view->set('link',  $link);
		
	}
	
	public function read($id, $_ = null) {
		
		if ($_=== null) {
			$media   = db()->table('media')->get('uniqid', $id)->first(true);
			$bucket  = $media->bucket;
		}
		else {
			$bucket = db()->table('bucket')->get('uniqid', $id)->first(true);
			$media  = db()->table('media')->get('name', $_)->where('bucket', $bucket)->first(true);
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
		
		$latest  = db()->table('revision')->get('media', $media)->where('expires', null)->first(true);
		$revs    = db()->table('revision')->get('media', $media)->all();
		$links   = db()->table('link')->get('media', $media)->all();
		
		$files   = db()->table('file')->get('revision', $latest)
			->where('commited', true)
			->group()->where('expires', null)->where('expires', '>', time())->endGroup()
			->all();
		
		$this->view->set('latest', $latest);
		$this->view->set('media', $media);
		$this->view->set('bucket', $bucket);
		$this->view->set('revs',   $revs);
		$this->view->set('files',  $files);
		$this->view->set('links',  $links);
	}
	
	public function update($id) {
		//TODO: Implement
		
	}
	
	public function delete($id, $_ = null) {
		
		if ($_=== null) {
			$media   = db()->table('media')->get('uniqid', $id)->first(true);
			$bucket  = $media->bucket;
		}
		else {
			$bucket = db()->table('bucket')->get('uniqid', $id)->first(true);
			$media  = db()->table('media')->get('name', $_)->where('bucket', $bucket)->first(true);
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
		
		$media->expires = time();
		$media->store();
		
		$latest = db()->table('revision')->get('media', $media)->where('expires', null)->first(true);
		$latest->expires = time();
		$latest->store();
		
		$files = db()->table('file')->get('revision', $latest)->all();
		
		foreach ($files as $file) {
			$file->expires = time();
			$file->commited = false;
			$file->store();
			
			$task = $this->tasks->get(\cloudy\task\FileUpdateTask::class);
			$task->load($file->uniqid);
			$this->tasks->send($file->server, $task);
		}
	}
	
}