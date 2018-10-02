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
	
	private $checksum;
	
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
		
		try { $storage = $dir->open('uniqid_' . $this->uniqid); }
		catch (\Exception$e) { $storage = $dir->make('uniqid_' . $this->uniqid); }
		
		try {
			
			if ($storage instanceof \spitfire\io\stream\StreamTargetInterface) {
				console()->info('Detected stream capable storage. Streaming...')->ln();
				$writer = $storage->getStreamWriter();
				$response = $request->stream(function($string) use ($writer) { return $writer->write($string); })->send();
			}
			else {
				$response = $request->send();
				$storage->write($response->html());
			}
			
			$response->expect(200);
		}
		catch (\spitfire\io\curl\BadStatusCodeException$e) {
			console()->error('Error fetching file ' . $this->uniqid)->ln();
			console()->error('Got code ' . $e->getCode())->ln();
			
			#If the file is not okay, we delete it
			$storage->delete();
		}
		

		$file->file = $storage->uri();
		$file->mime = $response->mime();
		
		if (md5_file($storage->getPath()) !== $this->checksum) {
			$error = true;
			console()->error('Checksum mismatch after downloading file ' . $this->uniqid)->ln();
			console()->error('-> Expected ' . $this->checksum)->ln();
			console()->error('-> Received ' . md5_file($storage->getPath()))->ln();
		}
		else {
			$error = false;
		}
		
		$file->uniqid = $this->uniqid;
		$file->checksum = $this->checksum;
		$file->server = $self;
		$file->commited = true;
		$file->expires  = !$error? null : time();
		$file->store();
		
		try {
			console()->info('Committing expiration: ' . $file->expires)->ln();;
			$request = request($server->hostname . '/file/commit/' . $this->uniqid . '.json');
			$request->header('Content-type', 'application/json');
			$request->post($this->keys()->pack($server->uniqid, ['expires' => $file->expires]));
			$request->send()->expect(200);
		}
		catch (\Exception$e) {
			console()->error('Could not commit file ' . $this->uniqid)->ln();
			
			$file->expires = time();
			$file->store();
		}
		
		$this->done();
	}

	public function load($settings) {
		$pieces = explode(':', $settings);
		$this->uniqid = array_shift($pieces);
		$this->checksum = array_shift($pieces);
	}

	public function save() {
		return sprintf('%s:%s', $this->uniqid, $this->checksum);
	}

	public function version() {
		return 1;
	}

}
