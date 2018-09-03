<?php namespace cloudy\task;

use cloudy\helper\KeyHelper;
use cloudy\helper\SettingsHelper;
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

class FileDistributeTask extends Task
{
	
	private $uniqid;
	
	public function execute($db) {
		
		$media   = $db->table('revision')->get('uniqid', $this->uniqid)->first(true);
		$bucket  = $media->media->bucket;
		$cluster = $bucket->cluster;
		
		$servers = $db->table('server')->get('cluster', $cluster)->all()->filter(function ($e) { return $e->role & Role::ROLE_SLAVE; });
		$weighted = [];
		
		$total    = 0;
		$replicas = max($servers->count(), $bucket->replicas);
		
		foreach ($servers as $server) {
			$weight = (int)(pow($server->free / $server->size, 2) * 100);
			$weighted[$weight + $total] = $server;
			$total+= $weight;
		}
		
		$reverse = array_reverse($weighted);
		
		for ($i = 0; $i < $replicas; $i++) {
			$rand = mt_rand(0, $total);
			
			foreach ($reverse as $weight => $server) {
				if ($weight < $rand) {
					$file = $db->table('file')->newRecord();
					$file->revision = $media;
					$file->server = $server;
					$file->commited = false;
					$file->store();
					
					$d = $this->dispatcher();
					$t = $d->get(FilePullTask::class);
					$t->load($file->uniqid);
					
					$d->send(
						$server, 
						$t
					);
						
					unset($reverse[$weight]);
						
					break;
				}
				
			}
		}
		
		$this->done(true);
	}

	public function load($settings) {
		$this->uniqid = $settings;
	}

	public function name() {
		return 'revision.file.distribute';
	}

	public function save() {
		return $this->uniqid;
	}

	public function version() {
		return 1;
	}

}
