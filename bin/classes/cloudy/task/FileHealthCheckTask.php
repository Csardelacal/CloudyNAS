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

abstract class FileHealthCheckTask extends Task
{
	
	private $server;
	
	private $from;
	
	private $to;
	
	private $chunk;
	
	private $result;
	
	public abstract function master($records);
	
	public abstract function target($records, $expected);
	
	public abstract function repair($uniqid);
	
	public function execute($db) {
		/*
		 * Identify the target of the task and the server currently running. If 
		 * they are not the same the server is executing the master part of the
		 * health check.
		 */
		$self    = db()->table('server')->get('uniqid', $db->table('setting')->get('key', 'uniqid')->first()->value)->first();
		$master  = db()->table('server')->get('cluster', $self->cluster)->all()->filter(function ($e) { return $e->role & \cloudy\Role::ROLE_MASTER;})->rewind();
		$target  = $db->table('server')->get('uniqid', $this->server)->first();
		
		if (!$this->min || !$this->max) {
			$min = db()->table('file')->get('server', $target)->group()->where('expires', null)->where('expires', '>', time())->endGroup()->setOrder('uniqid', 'ASC')->first()->uniqid;
			$max = db()->table('file')->get('server', $target)->group()->where('expires', null)->where('expires', '>', time())->endGroup()->setOrder('uniqid', 'DESC')->first()->uniqid;
		}
		else {
			$min = $this->min;
			$max = $this->max;
		}
		
		//console()->info(sprintf('Preparing healthcheck for files %s to %s from server %s', $min, $max, $target->uniqid))->ln();
		sleep(1);
		
		/*
		 * Get all the records that matchs the given range
		 */
		$records = db()->table('file')
			->get('server', $target)
			->group()->where('expires', null)->where('expires', '>', time())->endGroup()
			->where('uniqid', '>=', max($this->getProgress(), $min))
			->where('uniqid', '<=', $max)
			->setOrder('uniqid', 'ASC')
			->range(0, $this->chunk);
		
		$nr = $records->isEmpty()? null : db()
			->table('file')
			->get('server', $target)
			->group()->where('expires', null)->where('expires', '>', time())->endGroup()
			->where('uniqid', '>=', max($this->getProgress(), $min))
			->where('uniqid', '>', $records->last()->uniqid)
			->setOrder('uniqid', 'ASC')->first();
		 
		  $next = $nr? $nr->uniqid : null;
		
		
		/*
		 * Runs if the process is a slave
		 */
		if ($self->uniqid === $target->uniqid) {
			$success = $this->target($records, $this->result);
			
			if ($success) {
				return $next? $this->setProgress($next) : $this->done();
			}
			elseif($this->chunk == 1) {
				$this->repair($this->getProgress());
				$this->done();
				return;
			}
			else {
				$task = clone($this);
				$task->load(implode(':', [$target->uniqid, $min, $max, ceil($this->chunk / 10), null]));

				$this->dispatcher()->send($master, $task);
				return $next? $this->setProgress($next) : $this->done();
			}
		}
		/*
		 * Runs on the master
		 */
		else {
			$result = $this->master($records);
			
			if ($records->isEmpty()) {
				$this->done();
				return;
			}
			
			$task = clone($this);
			$task->load(implode(':', [$target->uniqid, max($this->getProgress(), $min), $records->last()->uniqid, $this->chunk, $result]));
			
			$this->dispatcher()->send($target, $task);
			
			return $next? $this->setProgress($next) : $this->done();
		}
	}
	
	public function load($settings) {
		list($this->server, $this->min, $this->max, $this->chunk, $this->result) = explode(':', $settings);
	}
	
	public function save() {
		return implode(':', [$this->server, $this->min, $this->max, $this->chunk, $this->result]);
	}
	
}
