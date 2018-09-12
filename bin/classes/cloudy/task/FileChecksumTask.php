<?php namespace cloudy\task;

use cloudy\helper\KeyHelper;
use cloudy\helper\SettingsHelper;
use cloudy\Role;
use function db;

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
	
	private $uniqid;
	
	public function execute($db) {
		
		$file     = $db->table('file')->get('uniqid', $this->uniqid)->first(true);
		$revision = $file->revision;
		$local    = db()->table('file')->get('revision', $revision)->where('file', '!=', null)->first(true);
		$self = db()->table('server')->get('uniqid', db()->table('setting')->get('key', 'uniqid')->first()->value)->first(true);
		
		try {
			if (!$local->checksum) {
				$local->checksum = md5_file(storage($local->file)->getPath());
				$local->store();
			}
		}
		catch(\Exception$e) {
			console()->error('Tried checksumming non-existent file ' . $this->uniqid)->ln();
			
			$task = $this->dispatcher()->get(FilePullTask::class);
			$task->load($this->uniqid);
			
			$this->dispatcher()->send($self, $task);
			
			return;
		}
		
		$file->checksum = $local->checksum;
		$file->store();
		
		$this->done();
	}

	public function load($settings) {
		$this->uniqid = $settings;
	}

	public function save() {
		return $this->uniqid;
	}

	public function version() {
		return 1;
	}

}
