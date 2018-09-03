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

class FilePullTask extends Task
{
	
	private $uniqid;
	
	public function execute($db) {
		$file = db()->table('file')->get('uniqid', $this->uniqid)->first()? : db()->table('file')->newRecord();
		$self = db()->table('server')->get('uniqid', db()->table('setting')->get('key', 'uniqid')->first()->value)->first(true);
		
		if ($file->file) {
			try {	storage($file->file)->delete(); }
			catch (\Exception$e) {}
		}
		
		$dir = storage(\spitfire\core\Environment::get('uploads.directory'));
		
		if (!$dir instanceof \spitfire\storage\objectStorage\DirectoryInterface) {
			throw new \spitfire\exceptions\PrivateException('Storage directory is not a directory', 1809031142);
		}
		
		$cluster = $self->cluster;
		$server  = db()->table('server')->get('cluster', $cluster)->where('active', true)->all()->filter(function($e) { return $e->role & \cloudy\Role::ROLE_MASTER; })->rewind();

		$request = request($server->hostname . '/file/retrieve/uniqid/' . $this->uniqid);
		$request->header('Content-type', 'application/json');
		$request->post($this->keys()->pack($server->uniqid, base64_encode(random_bytes(150))));

		$response = $request->send();

		try { $storage = $dir->open('uniqid_' . $this->uniqid); }
		catch (\Exception$e) { $storage = $dir->make('uniqid_' . $this->uniqid); }

		$storage->write($response->html());

		$file->file = $storage->uri();
		$file->mime = $response->mime();
		
		$file->uniqid = $this->uniqid;
		$file->server = $self;
		$file->commited = true;
		$file->store();
		
		
		$request = request($server->hostname . '/file/commit/' . $this->uniqid . '.json');
		$request->header('Content-type', 'application/json');
		$request->post($this->keys()->pack($server->uniqid, base64_encode(random_bytes(150))));
		$request->send()->expect(200);
		
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
