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

class RevisionHealthCheckTask extends Task
{
	
	private $chunk = 1000;
	
	public function execute($db) {
		
		
		$self = db()->table('server')->get('uniqid', $db->table('setting')->get('key', 'uniqid')->first()->value)->first();
		
		/*
		 * Get all the records that matchs the given range
		 */
		$query = db()->table('revision')
			->getAll()
			->group()->where('expires', null)->where('expires', '>', time())->endGroup()
			->where('_id', '>', $this->getProgress())
			->setOrder('uniqid', 'ASC');
		
		$records = $query->range(0, $this->chunk);
		
		foreach ($records as $record) {
			
			if ($record->media->expires !== null && $record->expires === null) {
				$record->expires = $record->media->expires;
				$record->store();
				console()->info('Fixed expiration of revision.')->ln();
			}
			
			/*
			 * Check if the revision has enough replicas of itself
			 */
			$files = db()->table('file')
				->get('revision', $record);
			
			if ($record->expires) {
				$files->group()->where('expires', null)->where('expires', '>=', $record->expires? $record->expires : time())->endGroup();
			}
			else {
				$files->where('expires', null);
			}
			
			if ($files->count() == 0) {
				console()->error('Removing orphaned revision ' . $record->uniqid)->ln();
				$record->expires = time();
				$record->store();
			}
			elseif ($files->count() < $record->media->bucket->replicas) {
				$task = $this->dispatcher()->get(FileDistributeTask::class);
				$task->load($record->uniqid);
				
				$this->dispatcher()->send($self, $task);
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
