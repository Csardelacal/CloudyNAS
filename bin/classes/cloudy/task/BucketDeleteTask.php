<?php namespace cloudy\task;

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

class BucketDeleteTask extends Task
{
	
	private $uniqid;
	
	public function execute($db) {
		
		$self = db()->table('server')->get('uniqid', db()->table('setting')->get('key', 'uniqid')->first()->value)->first(true);
		$bucket = db()->table('bucket')->get('uniqid', $this->uniqid)->first();
		
		if (!$bucket) {
			$this->done();
			return;
		}
		
		/**
		 * The server is a leader, and therefore can remove the bucket from the topography.
		 */
		if ($self->role & \cloudy\Role::ROLE_LEADER) {
			#The leader is in charge of creating this task on all the members 
			$all = db()->table('server')->getAll()->all();
			
			foreach ($all as $server) {
				$d = $this->dispatcher();
				$t = $d->get(BucketDeleteTask::class);
				$t->load($this->uniqid);
				$d->send($server, $t);
			}
		}
		
		if (!($self->role & \cloudy\Role::ROLE_MASTER)) {
			#Slaves only know the buckets from the topography, we can delete this too
			#The leader can also delete the bucket once it's been dispatched to all the other servers
			$bucket->delete();
			$this->done();
		}
		
		if ($self->role & \cloudy\Role::ROLE_MASTER) {
			$medias = db()->table('media')->get('bucket', $bucket)->where('expires', null)->range(0, 100);
			
			foreach ($medias as $media) {
				$media->expires = time();
				$media->store();
				
				$latest = db()->table('revision')->get('media', $media)->where('expires', null)->first(true);
				$latest->expires = time();
				$latest->store();

				$files = db()->table('file')->get('revision', $latest)->all();

				foreach ($files as $file) {
					$file->expires = time();
					$file->commited = false;
					$file->store();

					$task = $this->dispatcher()->get(\cloudy\task\FileUpdateTask::class);
					$task->load($file->uniqid);
					$this->dispatcher()->send($file->server, $task);
				}
			}
			
			if (!$medias->isEmpty()) {
				$this->setProgress($media->_id);
				return;
			}
			elseif (!db()->table('media')->get('bucket', $bucket)->first()) {
				$bucket->delete();
				$this->done();
			}
			else {
				$this->setProgress(0);
				$this->setScheduled(time() + 86400);
				return;
			}
		}
		
	}

	public function load($settings) {
		$this->uniqid = $settings;
	}

	public function save() {
		return $this->uniqid;
	}

	public function version() {
		return 1;
	}

}
