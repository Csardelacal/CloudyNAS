<?php

use cloudy\helper\KeyHelper;
use cloudy\helper\SettingsHelper;
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
		$start = time();
		
		$file = spitfire()->getCWD() . '/bin/usr/.cron.lock';
		$fh = fopen($file, file_exists($file)? 'r' : 'w+');
		
		if (!flock($fh, LOCK_EX)) { 
			console()->error('Could not acquire lock')->ln();
			return 1; 
		}
		
		$flipflop = new \cron\FlipFlop(realpath($file));
		
		console()->info('Started queue')->ln();
		
		do {
		
			/*
			 * Load the tasks the server is supposed to be processing. We limit it at
			 * 10, since it's unlikely that the server will be able to handle many heavy
			 * tasks within one cycle of the cron.
			 */
			$tasks = db()->table('task\queue')->getAll()->setOrder('scheduled', 'ASC')->range(0, 10);

			/*
			 * Assemble the settings helper so we can quickly read the data from the 
			 * server's configuration.
			 */
			$settings = new SettingsHelper(db()->setting);

			/*
			 * Prepare the keys. This allows the tasks to defer behavior to another 
			 * server when needed.
			 */
			$keys = new KeyHelper(db(), $settings->read('uniqid'), $settings->read('pubkey'), $settings->read('privkey'));

			/*
			 * Initialize the task dispatcher. This object is in charge of fetching the
			 * tasks.
			 */
			$dispatcher = new TaskDispatcher($keys);

			/*
			 * Loop over the pending tasks.
			 */
			foreach ($tasks as $task) {
				$p = $dispatcher->get($task->job);

				/*
				 * If the task cannot be executed at all, we will assume that the system
				 * is being upgraded and a different server on the network received the
				 * upgrade first.
				 */
				if (!$p || $task->version > $p->version() + 1) {
					$task->scheduled = $task->scheduled + 3600;
					$task->store();
					continue;
				}

				/*
				 * Restore the state the task had before being stopped. This means, pushing
				 * the settings and the progress back to the task.
				 */
				$p->load($task->settings);
				$p->setProgress($task->progress);

				$p->execute(db());

				if ($p->isDone()) {
					$task->delete();
				}
				else {
					$task->progress = $p->getProgress();
					$task->scheduled = time() + 10; #Defer long running tasks so they don't clog up the system
					$task->store();
				}
				
				
				console()->success('Processed task ' . $task->job)->ln();
			}
			
			/*
			 * If the loop has been running for more than 20 minutes. Snap out of it.
			 */
			if (time() - $start > 1200) { break; }
		} 
		while (db()->table('task\queue')->get('scheduled', time(), '<=')->count() > 0 || $flipflop->wait());
		
		console()->info('Queue ended')->ln();
	}
	
}
