<?php namespace cron;

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

class DiscoveryCron extends Cron
{
	
	public function execute($state) {
		
		$table   = db()->table('server');
		$refresh = $table->get('lastSeen', time() - 3600, '<')->fetchAll();
		
		$refresh->each(function ($e) {
			$url = $e->hostname . '/server/info.json';
			$ch  = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_exec($ch);
			
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			
			/*
			 * If the server is responding properly, then we report the server as
			 * properly running and remove the reminder to automatically check after
			 * it.
			 */
			if ($status === 200) {
				$e->lastSeen = time();
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
		return 1200;
	}

	public function getName() {
		return 'discovery';
	}

}