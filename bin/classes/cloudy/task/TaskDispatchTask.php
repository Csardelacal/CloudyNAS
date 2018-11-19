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


class TaskDispatchTask extends Task
{
	
	private $task;
	
	public function execute($db) {
		if (!$this->task->body) {
			$this->done();
			return;
		}
		
		try {
			
			$r = request(rtrim($this->task->hostname, '/') . '/task/queue.json');
			$r->header('Content-type', 'application/json');
			$r->post($this->task->body);
			
			$response = $r->send();
		   $body = $response->html();
		  
		   $response->expect(200);
		} catch (\Exception $ex) {
			console()->error($ex->getMessage())->ln();
			console()->error($body)->ln();
		}
		
		$this->done();
	}

	public function load($settings) {
		$this->task = json_decode(is_array($settings)? json_encode($settings) : $settings);
	}

	public function save() {
		return json_encode($this->task);
	}

	public function version() {
		return 1;
	}
	
	public function accessLevel() {
		return \cloudy\Role::ROLE_MASTER;
	}

}
