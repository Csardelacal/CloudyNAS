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
		$file->store();
	}
	
	public function retrieve($type, $id) {
		$self = db()->table('server')->get('uniqid', $this->settings->read('uniqid'))->first(true);
		
		if ($type === 'uniqid' && in_array($this->_auth, [AuthenticatedController::AUTH_INT, AuthenticatedController::AUTH_APP])) {
			
			$file = db()->table('file')->get('uniqid', $id)->first();
			
			$revision = $file->revision;
			$local    = db()->table('file')->get('revision', $revision)->where('server', $self)->first(true);
			
		}
		elseif($self->role & cloudy\Role::ROLE_MASTER) {
			$link = db()->table('link')->get('uniqid', $id)->first(true);
			$media = $link->media;
			
			$revision = db()->table('revision')->get('media', $media)->where('expires', null)->first(true);
			$local = db()->table('file')->get('revision', $revision)->where('server', $self)->first();
		}
		else {
			$memcached = new \spitfire\cache\MemcachedAdapter();
			$file = $memcached->get('link_' . $id, function () use ($self, $id) {
				$master  = db()->table('server')->get('cluster', $self->cluster)->all()->filter(function ($e) { return $e->role & cloudy\Role::ROLE_MASTER; })->rewind();
				
				$request = request($master->hostname . '/link/read/' . $id . '.json');
				$request->header('Content-type', 'application/json')
				->post($this->keys->pack($master->uniqid, base64_encode(random_bytes(150))));
				
				$files = $request->send()->expect(200)->json()->files;
				
				foreach ($files as $file) {
					if ($self->uniqid == $file->server) { return $file->uniqid; }
				}
				
			});
			
			$local = db()->table('file')->get('uniqid', $file)->first();
		}
		
		if ($local && $local->file) {
			$this->response->setBody(storage($local->file)->read())->getHeaders()
				->set('Content-type', $local->mime);
		}
		elseif($self->role & cloudy\Role::ROLE_MASTER) {
			$files = db()->table('file')->get('revision', $revision)->group()->where('expires', null)->where('expires', '>', time())->endGroup()->where('commited', true)->first(true);
			$server = $files[rand(0, $files->count() - 1)]->server->hostname;
			
			return $this->response->setBody('Redirect...')->getHeaders()->redirect($server . '/file/retrieve/link/' . $id);
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