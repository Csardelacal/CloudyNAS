<?php namespace cron;

use cloudy\Role;
use function db;

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
		 * A server can only be attached to one cluster. This setting is ignored
		 * by pool servers. A server cannot master and slave different clusters,
		 * nor can a single server master several clusters or serve several clusters.
		 */
		$cluster = db()->setting->get('key', 'cluster')->fetch()->value;
		
		/*
		 * Get the list of servers that this one is familiar with.
		 */
		$table   = db()->table('server');
		$refresh = $table->get('lastSeen', time() - 1200, '<')->fetchAll();
		
		$refresh->each(function ($e) use ($uniqid, $cluster) {
			
			/*
			 * If the server is the same server as we are managing, then skip it. We
			 * don't need to check whether the server can reach itself.
			 */
			if ($e->uniqid === $uniqid) {
				return true;
			}
			
			/*
			 * The discovery process can read the information broadcasted by every
			 * server about it's network and assimilate the nodes it has become
			 * aware of.
			 */
			
			//TODO: The remote server is signing this request. And the local server
			//should verify that the signature is acknowledgeable.
			
			//TODO: The local server should authorize the request, so network topology
			//is not leaked.
			$url = $e->hostname . '/server/info.json';
			
			/*
			 * Launch a request to retrieve the remote server's status and 
			 */
			$ch  = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$raw = curl_exec($ch);
			
			$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$response = json_decode($raw)->payload;
			
			/*
			 * If the server is responding properly, then we report the server as
			 * properly running and remove the reminder to automatically check after
			 * it.
			 * 
			 * The server is always considered an authority on it's own status (only
			 * to be overriden by a pool server)
			 */
			var_dump($raw); die();
			if ($status === 200) {
				$e->lastSeen = time();
				$e->size     = $response->disk->size?? null;
				$e->free     = $response->disk->free?? null;
				$e->cluster  = $response->cluster;
				$e->pubKey   = $response->pubkey;
				$e->role     = $response->role;
				$e->active   = $response->active;
				$e->disabled = $response->disabled;
				$e->store();
				
				//TODO: The server is going to broadcast a set of siblings over the
				//network, these should be acknowledged
			}
			
			/*
			 * If the server has not been seen in the last month, then we just 
			 * ignore it ever existed.
			 */
			elseif ($e->lastSeen < time() - 86400 * 30) {
				$e->delete();
			}
			
			else {
				//TODO: If the server is not reachable and has not yet been abandoned, 
				//the system should raise an alert.
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