<?php namespace cloudy\task;

use cloudy\helper\KeyHelper;
use cloudy\Role;
use function db;
use function request;

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
 * This cron is used by the system to establish a basic network topography. 
 * Please note that this cron is only used by pool servers and masters, allowing
 * the system to discover peers and know the state of their slaves.
 * 
 * Every master is aware of the entire network. While a master doesn't need to
 * specifically ping every slave (since they can rely on the disk usage reported
 * by other masters) they will be aware of their existence.
 * 
 * A pool owner (unless it's also a master) will never ping slaves, and rely
 * solely on the data reported by he appropriate masters. Please note, that this
 * introduces potential inaccuracies when reading the data in the dashboard since
 * there may be a few hours of delay between reported and actual data.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class LeaderDiscoveryTask extends Task
{
	
	public function execute($state) {
		
		$uniqid  = db()->setting->get('key', 'uniqid')->fetch()->value;
		$self = db()->table('server')->get('uniqid', $uniqid)->first();
		
		if (!($self->role & Role::ROLE_LEADER)) {
			/*
			 * This server does not need to execute this cron. Only leaders will have
			 * the ability to do so.
			 */
			$this->done();
			return;
		}
		
		$dir = storage()->dir(\spitfire\core\Environment::get('uploads.directory'));
		$self->size = disk_total_space($dir->getPath());
		$self->free = disk_free_space($dir->getPath());
		$self->store();
		
		/*
		 * Get the list of servers that this one is familiar with.
		 */
		$refresh = db()->table('server')->getAll()->all();
		
		
		/*
		 * Generate a signature that is used to sign this request against the receiving
		 * servers.
		 */
		$keys = new KeyHelper(db(), $uniqid, db()->setting->get('key', 'pubkey')->fetch()->value, db()->setting->get('key', 'privkey')->fetch()->value);
		
		$refresh->each(function ($e) use ($keys) {
			
			
			/*
			 * Launch a request to retrieve the remote server's status and 
			 */
			$r = request($e->hostname . '/server/info.json');
			
			$response = $r
				->header('Content-type', 'application/json')
				->post($keys->pack($e->uniqid, base64_encode(random_bytes(150))))
				->send()->expect(200)->json()->payload;
			
			if (empty($response)) {
				console()->error('Could not discover server ' . $e->uniqid)->ln();
				console()->error($r->header('Content-type', 'application/json')->post($keys->pack($e->uniqid, base64_encode(random_bytes(150))))->send()->html());
				return;
			}
			
			/*
			 * If the server is responding properly, then we report the server as
			 * properly running and remove the reminder to automatically check after
			 * it.
			 * 
			 * The server is always considered an authority on it's own status (only
			 * to be overriden by a pool server)
			 */
			$e->lastSeen = time();
			$e->lastCron = $response->lastCron;
			$e->queueLen = $response->queue->length?? 0;
			$e->size     = $response->disk->size?? null;
			$e->free     = $response->disk->free?? null;
			$e->writable = $response->disk->writable?? null;
			$e->pubKey   = $response->pubkey;
			$e->store();
		});
		
		$this->done();
	}

	public function load($settings) {
		return;
	}

	public function save() {
		return;
	}

	public function version() {
		return 1;
	}

}