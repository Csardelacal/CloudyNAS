<?php

use cloudy\task\TaskDispatcher;
use spitfire\mvc\Director;

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

class QueueDirector extends Director
{
	
	public function process() {
		$tasks = db()->table('task\queue')->getAll()->setOrder('scheduled', 'ASC')->range(0, 10);
		$dispatcher = new TaskDispatcher();
		
		foreach ($tasks as $task) {
			$p = $dispatcher->get($task->job);
			
			if ($task->version > $p->version() + 1) {
				$task->scheduled = $task->scheduled + 3600;
				$task->store();
				continue;
			}
			
			$p->load($task->settings);
			$p->setProgress($task->progress);
			
			$p->execute(db());
			
			if ($p->isDone()) {
				$task->delete();
			}
			else {
				$task->progress = $p->getProgress();
				$task->scheduled = time(); #Defer long running tasks so they don't clog up the system
				$task->store();
			}
		}
	}
	
}
