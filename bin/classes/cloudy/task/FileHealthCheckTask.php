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

class FileHealthCheckTask extends Task
{
	
	private $server;
	
	private $min;
	
	private $max;
	
	private $chunk = 1000;
	
	private $result;
	
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
		
		
		/*
		 * Get all the records that matchs the given range
		 */
		$query = db()->table('file')
			->get('server', $target)
			->group()->where('expires', null)->where('expires', '>', time())->endGroup()
			->where('uniqid', '>=', max($this->getProgress(), $min))
			->where('uniqid', '<=', $max)
			->setOrder('uniqid', 'ASC');
		
		$records = $self->uniqid === $master->uniqid? $query->range(0, $this->chunk) : $query->all();
		
		if ($self->uniqid === $master->uniqid) {
			return $this->master($target, $records);
		}
		
		elseif($self->uniqid === $target->uniqid) {
			return $this->slave($target, $records);
		}
		
	}
	
	public function master($target, $records) {
		
		$nr = $records->isEmpty()? null : db()
			->table('file')
			->get('server', $target)
			->group()->where('expires', null)->where('expires', '>', time())->endGroup()
			->where('uniqid', '>', $records->last()->uniqid)
			->setOrder('uniqid', 'ASC')->first();

		$next = $nr? $nr->uniqid : null;
		
		if ($records->isEmpty()) {
			$this->done();
			return;
		}
		
		
		$task = clone($this);
		$task->load(json_encode([
			$target->uniqid, 
			$records->rewind()->uniqid, 
			$records->last()->uniqid, 
			$records->each(function ($e) { return $e->uniqid; })->toArray()
		]));
		
		$this->dispatcher()->send($target, $task);
		return $next? $this->setProgress($next) : $this->done();
	}
	
	public function slave($target, $records) {
		$remote = collect($this->result);
		$local  = $records;

		$left  = $local->shift();
		$right = $remote->shift();

		while ($left || $right) {
			sleep(1);
			/*
			 * The records are equal, therefore nothing happens.
			 */
			if ($right && $left && $left->uniqid == $right) {
				console()->success(sprintf('Health of file %s confirmed', $left->uniqid))->ln();
				$left  = $local->shift();
				$right = $remote->shift();
			}

			elseif ($right && (!$left || $left->uniqid > $right)) {
				console()->info(sprintf('File %s is missing on slave. Requesting...', $right))->ln();
				$task = $this->dispatcher()->get(FileUpdateTask::class);
				$task->load($right);
				$this->dispatcher()->send($target, $task);

				$right = $remote->shift();
			}

			/*
			 * If a file exists locally that does not exist remotely, we do need
			 * to perform an additional check. The file may have been created while
			 * performing the healthcheck.
			 */
			elseif ($left && (!$right || $right > $left->uniqid)) {

				if ($left->created < time() - 3600) {
					console()->info(sprintf('File %s is missing on master. Deleting', $left->uniqid))->ln();
					$left->expires = time();
					$left->store();
				}
				else {
					console()->info(sprintf('Apparently recent file %s found. Skipping', $left->uniqid))->ln();
				}

				$left = $local->shift();
			}
		}

		$this->done();
	}
	
	public function load($settings) {
		list($this->server, $this->min, $this->max, $this->result) = json_decode($settings);
	}
	
	public function save() {
		return json_encode([$this->server, $this->min, $this->max, $this->result]);
	}

	public function version() {
		return 1;
	}

}
