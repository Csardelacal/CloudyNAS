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

class FileController extends AuthenticatedController
{
	
	public function read($id) {
		
		if ($this->_auth === AuthenticatedController::AUTH_NONE) {
			throw new \spitfire\exceptions\PrivateException('Not authorized', 403);
		}
		//TODO: Add condition for authenticated apps
		
		$file = db()->table('file')->get('uniqid', $id)->first();
		
		$this->view->set('file', $file);
	}
	
	public function commit($uniqid) {
		if ($this->_auth !== AuthenticatedController::AUTH_INT) {
			throw new \spitfire\exceptions\PrivateException('Not authorized', 403);
		}
		
		$file = db()->table('file')->get('uniqid', $uniqid)->first(true);
		
		$file->commited = true;
		$file->expires  = isset($_POST['expires'])? $_POST['expires'] : null;
		$file->store();
		
		if ($file->expires != $file->revision->expires) {
			$task = $this->tasks->get(cloudy\task\FileDistributeTask::class);
			$task->load($file->revision->uniqid);
			
			$this->tasks->send(db()->table('server')->get('uniqid', $this->settings->read('uniqid'))->first(), $task);
		}
	}
	
	public function retrieve($type, $id, $revisionid = null) {
		/*
		 * First, we need to determine which server we ourselves are. This allows 
		 * our server to take different approaches depending on it's own role inside
		 * the cluster.
		 */
		$self = db()->table('server')->get('uniqid', $this->settings->read('uniqid'))->first(true);
		
		/*
		 * When requesting a file via it's uniqid, we make sure that only authenticated
		 * siblings (other servers inside cloudyNAS) or application servers can access
		 * the data.
		 * 
		 * As opposed to links, uniqids are inmutable, and therefore cannot be revoked.
		 * This means that data could be leaked in an unstoppable manner if the 
		 * uniqid gets exposed at some point.
		 */
		if ($type === 'uniqid' && !in_array($this->_auth, [AuthenticatedController::AUTH_INT, AuthenticatedController::AUTH_APP])) {
			throw new PublicException('Not authenticated properly', 403);
		}
		
		/*
		 * Uniqid requests can only be handled by masters. If the server is a worker,
		 * then the request is rejected.
		 * 
		 * While this is not a requirement of the spec, it improves consistency, by 
		 * forcing applications to use the public links instead of sharing the 
		 * internal uniqids.
		 */
		if ($type === 'uniqid' && !($self->role & cloudy\Role::ROLE_MASTER)) {
			throw new PublicException('Invalid request', 400);
		}
		
		/*
		 * Get the file via it's uniqid. The uniqid identifies a file on a server,
		 * therefore making it autonomous. The master knows exactly which file is 
		 * being requested.
		 * 
		 * A server can host a file, without needing to know what relationship it 
		 * has to other files. The master knows how the file correlates with revisions
		 * and media, and therefore can correlate a connection.
		 * 
		 * This also prevents slaves from exchanging private data without the master
		 * being aware of it and able to protocol the requests. A uniqid is unique
		 * to every server, and therefore a sibling cannot infer the status of another 
		 * sibling.
		 * 
		 * The most prominent feature of this is that it allows the master to shift 
		 * files around slaves (potentially uploading the same file to a server twice,
		 * while keeping them in different states)
		 */
		if ($type === 'uniqid') {
			$file = db()->table('file')->get('uniqid', $id)->first();
			
			$revision = $file->revision;
			$local = db()->table('file')->get('revision', $revision)->where('server', $self)->first();
		}
		else {
			$revision = null;
			$local = $self->resolve($id, $revisionid);
		}
		
		if ($local && $local->file) {
			$this->response->setBody(storage($local->file)->read())->getHeaders()
				->set('Content-type', $local->mime);
		}
		elseif($self->role & cloudy\Role::ROLE_MASTER) {
			$files = db()->table('file')->get('revision', $revision)->group()->where('expires', null)->where('expires', '>', time())->endGroup()->where('commited', true)->all();
			
			if ($files->isEmpty()) { throw new PublicException('No candidate servers found', 404); }
			
			$server = $files[rand(0, $files->count() - 1)]->server->hostname;
			$link   = db()->table('link')->get('media', $revision->media)->where('expires', null)->first(true);
			
			return $this->response->setBody('Redirect...')->getHeaders()->redirect($server . '/file/retrieve/link/' . $link->uniqid . '/' . $revision->uniqid);
		}
		elseif($self->role & cloudy\Role::ROLE_SLAVE) {
			$server = db()->table('server')->get('cluster', $self->cluster)->all()->filter(function ($e) { return $e->role & cloudy\Role::ROLE_MASTER; })->rewind()->hostname;
			return $this->response->setBody('Redirect...')->getHeaders()->redirect($server . '/file/retrieve/link/' . $id);
		}
		else {
			throw new PublicException('File is not here', 404);
		}
	}
	
}