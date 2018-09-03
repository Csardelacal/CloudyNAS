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

/**
 * The configuration of any cloudy instance should be really barebones. Once the
 * server has a configuration file that tells it where the SSO server is and which
 * database to use, it can quickly and effectively start receiving tasks.
 * 
 * Tasks can be:
 * * Uploads
 * * Deletions
 * * Cron automated tasks (like discovery, health check, etc)
 */
class SetupController extends AuthenticatedController
{
	
	/**
	 * 
	 */
	public function index() {
		
		if ($this->settings->read('poolid')) {
			throw new PublicException('This server has already been set up.', 403);
		}
		
		if (!$this->user) {
			return $this->response->setBody('Redirect...')->getHeaders()->redirect(url('user', 'login', ['returnto' => (string)url('setup')]));
		}
		
		return $this->response->setBody('Redirect...')->getHeaders()->redirect(url('setup', 'run'));
	}
	
	public function run() {
		
		if ($this->settings->read('poolid')) {
			throw new PublicException('This server has already been set up.', 403);
		}
		
		if ($this->request->isPost() && isset($_POST['init'])) {
			/*
			 * Set a random pool id. This pool id prevents the servers from accepting
			 * commands from servers that were erroneously configured to access this
			 * pool.
			 * 
			 * Please note that this is not a measure to prevent attacks, but to prevent
			 * human error when managing servers that belong to different pools.
			 * Moving a server from one pool to another could be catastrophic, specially
			 * when moving a pool server.
			 */
			$this->settings->set('poolid', base64_encode(random_bytes(10)));
			
			/*
			 * Since we sent the command to initialize the pool, it means that this
			 * server is the first to this pool and therefore pool owner by default.
			 */
			$this->settings->set('role', Role::ROLE_LEADER);
			
			/*
			 * Record the pool server that is acquiring this server. This is a handshake
			 * that will cause the server to start accepting requests from this server
			 * alone until it gets introduced to further servers.
			 */
			$e = db()->table('server')->newRecord();
			$e->lastSeen = time();
			$e->size     = null;
			$e->free     = null;
			$e->cluster  = null;
			$e->hostname = url()->absolute();
			$e->uniqid   = $this->settings->read('uniqid');
			$e->pubKey   = $this->settings->read('pubkey');
			$e->role     = Role::ROLE_LEADER;
			$e->active   = true;
			$e->disabled = null;
			$e->store();
			
			return $this->response->setBody('Redirect...')->getHeaders()->redirect(url());
		}
		
		/*
		 * A pool is trying to acquire this server, it will provide this server
		 * with the necessary information to discover the pool on it's own.
		 */
		elseif ($this->request->isPost()) {
			
			$this->settings->set('poolid', $_POST['poolid']);
			$this->settings->set('role', Role::ROLE_SLAVE);
			
			/*
			 * Record the pool server that is acquiring this server. This is a handshake
			 * that will cause the server to start accepting requests from this server
			 * alone until it gets introduced to further servers.
			 */
			$e = db()->table('server')->newRecord();
			$e->lastSeen = time();
			$e->size     = null;
			$e->free     = null;
			$e->cluster  = null;
			$e->hostname = $_POST['hostname'];
			$e->uniqid   = $_POST['uniqid'];
			$e->pubKey   = $_POST['pubkey'];
			$e->role     = Role::ROLE_LEADER;
			$e->active   = true;
			$e->disabled = null;
			$e->store();
			
			/*
			 * Let the view respond with a success message to the other server,
			 * informing it that the request was a success and that this one is 
			 * now accepting tasks from it.
			 */
			$this->view->set('owner', $e);
			$this->view->set('uniqid', $this->settings->read('uniqid'));
			$this->view->set('pubkey', $this->settings->read('pubkey'));
		}
		
		/*
		 * This should be all. Once the server has been set up to be a pool owner we
		 * can redirect the user to the dashboard where they can either add other
		 * servers to the pool or assign additional roles to this server.
		 * 
		 * The template for this will display a success message and link the user 
		 * over to the dashboard page.
		 */
	}
	
}
