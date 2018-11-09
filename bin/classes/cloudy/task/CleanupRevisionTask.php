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

class CleanupRevisionTask extends Task
{
	
	public function execute($db) {
		$expired = $db->table('revision')->get('expires', time() - (10 * 86400), '<')->where('expires', '!=', null)->range(0, 100);
		
		foreach ($expired as $revision) {
			console()->info('Cleaning up revision ' . $revision->uniqid)->ln();
			$files = $db->table('file')->get('revision', $revision)->all();
			
			if ($files->isEmpty()) {
				$revision->delete();
				continue;
			}
			
			//Implicit else
			console()->error('Unsatisfied dependency when trying to delete revision ' . $revision->uniqid)->ln();

			$files->each(function($e) use ($revision) {
				$e->expires = $revision->expires;
				$e->store();

				$task = $this->dispatcher()->get(\cloudy\task\FileUpdateTask::class);
				$task->load($e->uniqid);
				$this->dispatcher()->send($e->server, $task);
			});

			$revision->expires = time();
			$revision->store();
		}
		
		if ($expired->isEmpty()) {
			$this->done();
		}
	}

	public function load($settings) {
		return;
	}

	public function save() {
		return;
	}

	public function version() {
		return 1;
	}

}
