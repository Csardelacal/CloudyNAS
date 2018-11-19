<?php

use spitfire\exceptions\PublicException;

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

class TaskController extends AuthenticatedController
{
	
	/**
	 * 
	 * @throws PublicException
	 */
	public function queue() {
		
		$self = db()->table('server')->get('uniqid', $this->settings->read('uniqid'))->first();
		
		if ($this->_auth !== AuthenticatedController::AUTH_INT) {
			throw new PublicException('Authentication of the remote application failed', 403);
		}
		
		$dispatcher = $this->tasks;
		
		$task = $dispatcher->get($_POST['job']);
		
		if (!$task) {
			throw new PublicException('No such task', 404);
		}
		
		if ($self && !($self | $task->accessLevel())) {
			trigger_error('Server received inappropriate task ' . $_POST['job'] . ' from ' . $_POST['source'], E_USER_NOTICE);
			throw new PublicException('Invalid task', 403);
		}
		
		$queue = db()->table('task\queue')->newRecord();
		$queue->job = $_POST['job'];
		$queue->version = $_POST['version'];
		$queue->settings = $_POST['settings'];
		$queue->scheduled = $_POST['scheduled']?? time();
		$queue->progress = null;
		$queue->store();
		
		$flipflop = new \cron\FlipFlop(realpath(spitfire()->getCWD() . '/bin/usr/.cron.lock'));
		$flipflop->notify();
	}
}
