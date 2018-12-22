<?php namespace cloudy\task;

use cloudy\Role;
use function console;
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

class FileDistributeTask extends TaskDispatchTask
{
	
	private $uniqid;
	
	public function execute($db) {
		console()->info('Distributing ' . $this->uniqid)->ln();
		
		$revision = $db->table('revision')->get('uniqid', $this->uniqid)->first(true);
		$bucket   = $revision->media->bucket;
		$cluster  = $bucket->cluster;
		
		$servers = $db->table('server')->get('cluster', $cluster)->where('active', true)->all()->filter(function ($e) { return $e->role & Role::ROLE_SLAVE; });
		$replicas = min((int)$servers->count(), $bucket->replicas);
		
		/*
		 * Remove the servers that already contain copies of the files. This ensures 
		 * that the application does not redistribute files to servers that already
		 * contain a copy.
		 */
		$query = db()->table('file')->get('revision', $revision);
		
		if ($revision->expires) {
			$query->group()->where('expires', null)->where('expires', '>=', $revision->expires)->endGroup();
		}
		else {
			$query->where('expires', null);
		}
		
		$existing = $query->all()->extract('server');
		
		foreach ($existing as $s) {
			$servers = $servers->filter(function ($e) use ($s) { return $e->uniqid !== $s->uniqid; });
		}
		
		/*
		 * Check if there is a source file.
		 */
		if (!$db->table('file')->get('revision', $revision)->group()->where('expires', null)->where('expires', '>', time())->endGroup()->where('commited', true)->first()) {
			$this->done();
			return;
		}
		
		for ($i = 0; $i < ($replicas - $existing->count()); $i++) {
			$weighted = [];
			$total    = 0;
			
			foreach ($servers as $server) {
				$size = $server->size + 1;
				$weight = (int)(pow($server->free / $size, 2) * 10000);
				$weighted[$weight + $total] = $server;
				$total+= $weight;
			}

			$rand = mt_rand(0, $total);
			
			foreach ($weighted as $weight => $server) {
				if ($weight >= $rand) {
					$file = $db->table('file')->newRecord();
					$file->revision = $revision;
					$file->server = $server;
					$file->commited = false;
					$file->store();
					
					$d = $this->dispatcher();
					$t = $d->get(FilePullTask::class);
					$t->load(sprintf('%s:%s', $file->uniqid, $revision->checksum));
					
					$d->send(
						$server, 
						$t
					);
						
					$servers->remove($server);
						
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
