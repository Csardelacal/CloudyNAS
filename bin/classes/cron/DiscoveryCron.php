<?php namespace cron;

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
class DiscoveryCron extends Cron
{
	
	public function execute($state) {
		
		$uniqid  = db()->setting->get('key', 'uniqid')->fetch()->value;
		
		/*
		 * Get the list of servers that this one is familiar with.
		 */
		$refresh = db()->table('server')->getAll()->all();
		
		$keys = new KeyHelper(db(), $uniqid, db()->setting->get('key', 'pubkey')->fetch()->value, db()->setting->get('key', 'privkey')->fetch()->value);
		
		$refresh->filter(function ($e) use ($uniqid) {
			
			/*
			 * If the server is the same server as we are managing, then skip it. We
			 * don't need to check whether the server can reach itself.
			 */
			if ($e->uniqid === $uniqid) {
				return false;
			}
			
			return $e->role & Role::ROLE_LEADER;
		})->each(function ($e) use ($keys) {
			
			
			/*
			 * Launch a request to retrieve the remote server's status and 
			 */
			
			$r = request($e->hostname . '/server/info.json');
			$r->get('s', base64_encode($keys->pack($e->uniqid, base64_encode(random_bytes(150)))));
			
			$response = $r->send()->expect(200)->json();
			
			if (!isset($response->payload)) {
				throw new \spitfire\exceptions\PrivateException('Leader returned an invalid response', 1808261635);
			}
			
			$payload  = $response->payload;
			
			/*
			 * If the server is responding properly, then we report the server as
			 * properly running and remove the reminder to automatically check after
			 * it.
			 * 
			 * The server is always considered an authority on it's own status (only
			 * to be overriden by a pool server)
			 */
			$e->lastSeen = time();
			$e->size     = $payload->disk->size?? null;
			$e->free     = $payload->disk->free?? null;
			$e->cluster  = $payload->cluster? db()->table('cluster')->get('uniqid', $payload->cluster)->first(true) : null;
			$e->pubKey   = $payload->pubkey;
			$e->role     = $payload->role;
			$e->active   = $payload->active;
			$e->disabled = $payload->disabled;
			$e->store();
			
			foreach ($payload->servers as $server) {
				$e = db()->table('server')->get('uniqid', $server->uniqid)->first()?: db()->table('server')->newRecord();
				$e->size     = $server->disk->size?? null;
				$e->free     = $server->disk->free?? null;
				$e->hostname = $server->hostname;
				$e->uniqid   = $server->uniqid;
				$e->cluster  = $server->cluster? db()->table('cluster')->get('uniqid', $server->cluster)->first(true) : null;
				$e->pubKey   = $server->pubkey;
				$e->role     = $server->role;
				$e->active   = $server->active;
				$e->disabled = $server->disabled;
				$e->store();
			}
		});
	}

	public function getInterval() {
		return 600;
	}

	public function getName() {
		return 'discovery';
	}

}