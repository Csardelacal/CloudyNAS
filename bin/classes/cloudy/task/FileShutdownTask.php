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

class FileShutdownTask extends Task
{
	
	public function execute($db) {
		
		$expired = $db->table('file')->getAll()->group()->where('expires', '>', time() + 3 * 86400)->where('expires', null)->endGroup()->range(0, 100);
		$self = db()->table('server')->get('uniqid', db()->table('setting')->get('key', 'uniqid')->first()->value)->first(true);
		
		$cluster = $self->cluster;
		$server  = db()->table('server')->get('cluster', $cluster)->where('active', true)->all()->filter(function($e) { return $e->role & \cloudy\Role::ROLE_MASTER; })->rewind();

		
		foreach ($expired as $file) {
			$file->expires = time() + 3 * 86400;
			$file->store();
			
			try {
				$request = request($server->hostname . '/file/commit/' . $file->uniqid . '.json');
				$request->header('Content-type', 'application/json');
				$request->post($this->keys()->pack($server->uniqid, ['expires' => $file->expires]));
				$request->send()->expect(200);
			}
			catch (\Exception$e) {
				console()->error('Error committing file ' . $file->uniqid)->ln();
			}
		}
		
		if (db()->table('file')->getAll()->count() == 0) {
			$this->done();
		}
		elseif($expired->isEmpty()) {
			$this->setScheduled(time() + 300);
		}
	}

	public function load($settings) {
		
	}

	public function save() {
		
	}

	public function version() {
		
	}

}
