<?php namespace cloudy\task;

use cloudy\helper\KeyHelper;
use cron\FlipFlop;
use ServerModel;
use spitfire\exceptions\PrivateException;
use function collect;
use function db;
use function request;
use function spitfire;

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

class TaskDispatcher
{
	
	/**
	 *
	 * @var KeyHelper
	 */
	private $keys;
	private $known;
	
	public function __construct($keys) {
		$this->keys = $keys;
		
		$this->known = collect();
		$this->known->push(FilePullTask::class);
		$this->known->push(FileUpdateTask::class);
		$this->known->push(FileDistributeTask::class);
		
		$this->known->push(FileChecksumTask::class);
		$this->known->push(FileHealthCheckTask::class);
		$this->known->push(RevisionHealthCheckTask::class);
		$this->known->push(QueueHealthCheckTask::class);
		
		$this->known->push(FileShutdownTask::class);
		
		$this->known->push(DiscoveryTask::class);
		$this->known->push(TopographyTask::class);
		$this->known->push(LeaderDiscoveryTask::class);
		
		$this->known->push(TaskDispatchTask::class);
		
		$this->known->push(CleanupFileTask::class);
		$this->known->push(CleanupRevisionTask::class);
		$this->known->push(CleanupMediaTask::class);
	}
	
	/**
	 * 
	 * @param type $name
	 * @return Task
	 */
	public function get($name) {
		foreach ($this->known as $known) {
			if ($known === $name) {
				return new $known($this);
			}
		}
		
		throw new PrivateException('Unknown task ' . $name, 1809121639);
	}
	
	/**
	 * 
	 * @return KeyHelper
	 */
	public function getKeys() {
		return $this->keys;
	}
		
	public function send(ServerModel$server, Task$task) {
		if ($this->keys->uniqid() === $server->uniqid) {
			/*
			 * Queue the task to the server's internal task queue.
			 */
			$queue = db()->table('task\queue')->newRecord();
			$queue->job = get_class($task);
			$queue->version = $task->version();
			$queue->settings = $task->save();
			$queue->scheduled = time();
			$queue->progress = null;
			$queue->store();

			$flipflop = new FlipFlop(realpath(spitfire()->getCWD() . '/bin/usr/.cron.lock'));
			$flipflop->notify();
		}
		else {
			$t    = $this->get(TaskDispatchTask::class);
			$self = db()->table('server')->get('uniqid', $this->keys->uniqid())->first();
			
			$t->load([
				'uniqid' => $server->uniqid,
				'hostname' => $server->hostname,
				'body' => $this->keys->pack($server->uniqid, [
					'job' => get_class($task),
					'version' => $task->version(),
					'settings' => $task->save(),
					'scheduled' => time()
				])
			]);
			
			$this->send($self, $t);
		}
	}
}
