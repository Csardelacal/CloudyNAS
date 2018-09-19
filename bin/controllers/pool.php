<?php

use cloudy\Role;
use spitfire\exceptions\HTTPMethodException;
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

class PoolController extends AuthenticatedController
{
	
	/**
	 * Adds a server to the network.
	 */
	public function acquire() {
		
		if ($this->_auth !== AuthenticatedController::AUTH_USER) {
			throw new PublicException('Not permitted', 403);
		}
		
		try {
			if (!$this->request->isPost()) { throw new HTTPMethodException('Not posted', 1807201122); }
			
			$self = db()->table('server')->get('uniqid', $this->settings->read('uniqid'))->first();
			
			/*
			 * Prepare the request to acquire the new server. This contains basic
			 * information about the pool being managed and the leader of the 
			 * cluster.
			 */
			$request = request(rtrim($_POST['hostname'], '/') . '/setup/run.json');
			$request->get('token', Session::getInstance()->getUser()->getId());
			$request->post('pubkey',   $this->settings->read('pubkey'));
			$request->post('poolid',   $this->settings->read('poolid'));
			$request->post('uniqid',   $this->settings->read('uniqid'));
			$request->post('hostname', $self? $self->hostname : url()->absolute());
			
			$response = $request->send()->expect(200)->json();
			
			/*
			 * Register the new worker. Please note that we will automatically set 
			 * the slave role in order to prevent the server from making any changes
			 * to the layout.
			 * 
			 * Once the server has been added, the pool should be able to send commands
			 * to it and start pushing tasks.
			 */
			$slave = db()->table('server')->newRecord();
			$slave->hostname = $_POST['hostname'];
			$slave->uniqid = $response->uniqid;
			$slave->pubKey = $response->pubkey;
			$slave->lastSeen = time();
			$slave->role     = Role::ROLE_SLAVE;
			$slave->active   = false;
			$slave->disabled = null;
			$slave->store();
		} 
		catch (HTTPMethodException $ex) {
			#Do nothing, show the form.
		}
	}
	
	public function info() {
		
		
		$uniqid = $this->settings->read('uniqid');
		
		if (!(db()->table('server')->get('uniqid', $uniqid)->first()->role & Role::ROLE_LEADER)) {
			throw new PublicException('This server is not an authority on the pool', 403);
		}
		
		/*
		 * Requests to this endpoint come empty. So we do not need to use the data
		 * the user sent, just make sure it's valid.
		 */
		if ($this->_auth !== AuthenticatedController::AUTH_INT) {
			throw new PublicException('Requests to this endpoint must be authorized.', 403);
		}
		
		$buckets  = db()->table('bucket')->getAll()->all();
		$clusters = db()->table('cluster')->getAll()->all();
		
		$this->view->set('buckets', $buckets);
		$this->view->set('clusters', $clusters);
	}
}