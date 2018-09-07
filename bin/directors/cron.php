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

class CronDirector extends Director
{
	
	public function run() {
		
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
		
		$run = [
			 cloudy\task\DiscoveryTask::class,
			 cloudy\task\LeaderDiscoveryTask::class,
			 cloudy\task\TopographyTask::class
		];
		
		foreach ($run as $t) {
			$task = $dispatcher->get($t);
			$dispatcher->send(db()->table('server')->get('uniqid', $settings->read('uniqid'))->first(true), $task);
			sleep(30);
		}
		
	}
	
}
