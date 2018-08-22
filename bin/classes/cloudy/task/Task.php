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

/**
 * Tasks are a lightweight mechanism to allow servers across Cloudy to send RPC
 * like messages across the network. Once a task is received by one server, it
 * will queue it and process it to it's closes convenience.
 * 
 * Please ntoe that
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
abstract class Task
{
	/**
	 * 
	 * @var string
	 */
	private $target;
	
	/**
	 * 
	 * @var string
	 */
	private $scheduled;
	
	/**
	 * 
	 * @var string 
	 */
	private $progress;
	
	/**
	 *
	 * @var bool
	 */
	private $done;
	
	
	public function getTarget() {
		return $this->target;
	}
	
	public function setTarget($target) {
		$this->target = $target;
		return $this;
	}
	
	public function getScheduled() {
		return $this->scheduled;
	}
	
	public function setScheduled($scheduled) {
		$this->scheduled = $scheduled;
		return $this;
	}
	
	public function getProgress() {
		return $this->progress;
	}
	
	public function setProgress($progress) {
		$this->progress = $progress;
		return $this;
	}
	
	public function isDone() {
		return $this->done;
	}

	public function done($done = true) {
		$this->done = $done;
		return $this;
	}
	
	public function accessLevel() {
		return \cloudy\Role::ROLE_MASTER | \cloudy\Role::ROLE_LEADER | \cloudy\Role::ROLE_SLAVE;
	}
		
	/**
	 * Returns the name of the task. This allows cross-server identification of 
	 * the tasks. This information is used to retrieve the proper tasks when the 
	 * data is retrieved from the queue.
	 */
	public abstract function name();
	
	/**
	 * Returns the version of the task. Every task must be backwards compatible one
	 * version. If two servers are exchanging tasks that are either more than one
	 * version apart, or the receiving task is unknown to the server, the receiving
	 * server will defer the task indefinitely before it receives an update that
	 * allows it to process the task.
	 * 
	 * A task that cannot be processed is not automatically discarded, but instead,
	 * deferred.
	 */
	public abstract function version();
	
	/**
	 * Loads the data the application provided via save.
	 */
	public abstract function load($settings);
	
	/**
	 * Allows the application to return a series of settings that can then be 
	 * serialized as JSON and written to the DB.
	 */
	public abstract function save();
	
	/**
	 * This is the core method of the task. When called, this should start processing
	 * the data inside a cloudy instance.
	 * 
	 * The function expects database access to do it's duty.
	 */
	public abstract function execute($db);
	
}
