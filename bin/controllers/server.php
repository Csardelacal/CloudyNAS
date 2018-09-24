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
	
	public function enable(ServerModel$server) {
		
		if ($server->active) {
			throw new PublicException('Server already active.', 400);
		}
		
		if (!$server->writable) {
			throw new PublicException('Server is not writable', 403);
		}
		
		if ($server->queueLen > 0) {
			throw new PublicException('Server is busy', 403);
		}
		
		/*
		 * If the server wishes to become a leader, we will need to ensure that it
		 * has all the necessary data pertaining servers, clusters and buckets.
		 * 
		 * After that, we could transfer the ownership of the pool to the new leader.
		 */
		if ($server->role & cloudy\Role::ROLE_LEADER) {
			#TODO: Implement
			throw new PublicException('Not implemented', 500);
		}
		elseif (!$server->cluster) {
			throw new PublicException('No cluster provided', 400);
		}
		
		$old = db()
			->table('server')
			->get('cluster', $server->cluster)
			->all()
			->filter(function($e) { return $e->role & cloudy\Role::ROLE_MASTER; })
			->rewind();
		
		if ($server->role & cloudy\Role::ROLE_MASTER && $old) {
			#TODO: Implement
			throw new PublicException('Not implemented', 500);
		}
		
		$server->active = true;
		$server->store();
		
		foreach (db()->table('server')->getAll()->all() as $server) {
			$this->tasks->send($server, $this->tasks->get(cloudy\task\DiscoveryTask::class));
		}
	}
	
	public function disable(ServerModel$server) {
		
		if ($server->role & cloudy\Role::ROLE_MASTER || $server->role & cloudy\Role::ROLE_LEADER) {
			throw new PublicException('You are required to appoint a new server for this task to disable masters or leaders', 403);
		}
		
		if (db()->table('server')->get('cluster', $server->cluster)->where('active', true)->all()->count() < db()->table('bucket')->get('cluster', $server->cluster)->all()->extract('replicas')->sort()->last()) {
			throw new PublicException('Removing this server would cripple the cluster. You must remove its buckets first', 403);
		}
		
		$server->active = false;
		$server->queueLen = $server->queueLen + 1;
		$server->store();
		
		$this->tasks->send($server, $this->tasks->get(cloudy\task\FileShutdownTask::class));
		
		foreach (db()->table('server')->getAll()->all() as $server) {
			$this->tasks->send($server, $this->tasks->get(cloudy\task\DiscoveryTask::class));
		}
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
		try {
			$dir = storage()->dir(Environment::get('uploads.directory'));

			$total = disk_total_space($dir->getPath());
			$free  = disk_free_space($dir->getPath());
			$write = $dir->isWritable();
		}
		catch (\Exception$e) {
			$total = 0;
			$free  = 0;
			$write = false;
		}
		
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
		$this->view->set('writable', !!$write);
		$this->view->set('lastCron', $this->settings->read('lastCron'));
	}
	
}
