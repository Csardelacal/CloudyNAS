<?php

use spitfire\core\Environment;
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

class ServerController extends AuthenticatedController
{
	
	public function read(ServerModel$server) {
		
		$this->view->set('server', $server);
	}
	
	public function setRole(ServerModel$server, $role) {
		
		if ($server->active) {
			throw new PublicException('Cannot change the server role while it is active.', 400);
		}
		
		$server->role = $role?: $_POST['role'];
		$server->store();
	}
	
	public function setCluster(ServerModel$server, ClusterModel$cluster = null) {
		
		if ($server->active) {
			throw new PublicException('Cannot change the server role while it is active.', 400);
		}
		
		$server->cluster = $cluster? : db()->table('cluster')->get('_id', $_POST['cluster'])->first(true);
		$server->store();
	}
	
	public function info() {
		
		if ($this->_auth !== AuthenticatedController::AUTH_INT) {
			throw new PublicException('Not authorized', 403);
		}
		
		/**
		 * The pool ID is a random string that the pool generates and that the 
		 * servers maintain to ensure that they belong to the same pool. This is 
		 * generated once, when the pool is set-up and then never changed.
		 * 
		 * If a master or slave receives instructions for a pool that it does not
		 * belong to, it will ignore these.
		 */
		$poolid   = $this->settings->read('poolid');
		$uniqid   = $this->settings->read('uniqid');
		$pubkey   = $this->settings->read('pubkey');
		
		$active   = $this->settings->read('active');
		$disabled = $this->settings->read('disabled');
		
		/*
		 * Generate a server directory. This allows the servers to exchange each 
		 * other and to create a topology of siblings. These directories are 
		 * only meant to be kept by pools and masters, slaves are free to be 
		 * unaware of the topology - reducing load on the network.
		 */
		$servers = db()->table('server')->get('uniqid', $uniqid, '!=')->fetchAll()->each(function ($e) {
			return [ 
				'hostname' => $e->hostname, 
				'uniqid' => $e->uniqid, 
				'pubkey' => $e->pubKey,
				'role' => $e->role, 
				'disk' => [
					'size' => $e->size, 
					'free' => $e->free,
					'writable' => !!$e->writable
				], 
				'cluster' => $e->cluster? $e->cluster->uniqid : null,
				'updated' => $e->lastSeen,
				'lastCron'=> $e->lastCron,
				'active'  => $e->active,
				'disabled' => $e->disabled
			]; 
		});
		
		
		#TODO: Provide info about the buckets the server hosts
		#TODO: Provide info about the cluster / masters
		$dir = storage()->dir(Environment::get('uploads.directory'));
		
		$total = disk_total_space($dir->getPath());
		$free  = disk_free_space($dir->getPath());
		
		$self = db()->table('server')->get('uniqid', $uniqid)->first();
		
		$this->view->set('uniqid',   $uniqid);
		$this->view->set('role',     $self->role);
		$this->view->set('poolid',   $poolid);
		$this->view->set('pubkey',   $pubkey);
		$this->view->set('cluster',  $self->cluster? $self->cluster->uniqid : null);
		$this->view->set('servers',  $servers);
		$this->view->set('active',   $active);
		$this->view->set('disabled', $disabled);
		$this->view->set('queueLen', db()->table('task\queue')->getAll()->count());
		$this->view->set('size',     $total);
		$this->view->set('free',     $free);
		$this->view->set('writable', !!storage()->dir(Environment::get('uploads.directory'))->isWritable());
		$this->view->set('lastCron', $this->settings->read('lastCron'));
	}
	
}
