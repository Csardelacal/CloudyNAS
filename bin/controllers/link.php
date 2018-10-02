<?php

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
		//TODO: Implement
	}
	
	public function read($uniqid, $revid = null) {
		
		if(!in_array($this->_auth, [AuthenticatedController::AUTH_INT, AuthenticatedController::AUTH_APP])) {
			throw new \spitfire\exceptions\PublicException('Insufficient permissions', 401);
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
