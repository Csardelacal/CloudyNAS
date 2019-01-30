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

class FileChecksumTask extends Task
{
	
	private $chunk = 100;
	
	public function execute($db) {
		
		$self    = db()->table('server')->get('uniqid', $db->table('setting')->get('key', 'uniqid')->first()->value)->first();
		$master  = db()->table('server')->get('cluster', $self->cluster)->all()->filter(function ($e) { return $e->role & \cloudy\Role::ROLE_MASTER;})->rewind();
		
		/*
		 * Get all the records that matchs the given range
		 */
		$query = db()->table('file')
			->getAll()
			->where('_id', '>', $this->getProgress())
			->group()->where('expires', null)->where('expires', '>', time())->endGroup()
			->where('server__id', $self->_id)
			->setOrder('_id', 'ASC');
		
		$records = $query->range(0, $this->chunk);
		
		foreach ($records as $record) {
			try { $file = storage()->get($record->file)->getPath(); }
			catch (\Exception$e) {
				$record->expires = time();
				$record->store();
				
				if ($master->uniqid === $self->uniqid) {continue; }
				
				$request = request($master->hostname . '/file/commit/' . $record->uniqid . '.json');
				$request->header('Content-type', 'application/json');
				$request->post($this->keys()->pack($master->uniqid, ['expires' => $record->expires]));
				$request->send()->expect(200);
				return;
			}
			
			if ($record->checksum !== md5_file($file)) {
				try { storage()->get($record->file)->delete(); }
				catch (\Exception$e) {}
				
				$record->expires = time();
				$record->store();
				
				console()->error('Checksum mismatch on file ' . $record->uniqid)->ln();
				console()->error('-> Expected ' . $record->checksum)->ln();
				console()->error('-> Received ' . md5_file($file))->ln();
				
				#If the master himself is running into the issue, there is no need to inform the master
				#TODO: Refactor this block somewhere else.
				if ($master->uniqid === $self->uniqid) {continue; }
				
				$request = request($master->hostname . '/file/commit/' . $record->uniqid . '.json');
				$request->header('Content-type', 'application/json');
				$request->post($this->keys()->pack($master->uniqid, ['expires' => $record->expires]));
				$request->send()->expect(200);
			}
		}
		
		$records->isEmpty()? $this->done() : $this->setProgress($record->_id);
	}
	
	public function load($settings) {
		return;
	}
	
	public function save() {
		return '';
	}

	public function version() {
		return 1;
	}

}
