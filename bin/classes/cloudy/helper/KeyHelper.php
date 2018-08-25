<?php namespace cloudy\helper;

use spitfire\exceptions\PrivateException;

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

class KeyHelper
{
	
	private $db;
	private $uniqid;
	private $pub;
	private $priv;
	
	public function __construct($db, $uniqid = null, $public = null, $private = null) {
		$this->db = $db;
		$this->uniqid = $uniqid;
		$this->priv = new \cloudy\encryption\PrivateKey($private);
		$this->pub = new \cloudy\encryption\PublicKey($public);
	}
	
	public function priv() {
		return $this->priv;
	}
	
	public function pub() {
		return $this->pub;
	}
	
	public function server($uniqid) {
		$s = $this->db->table('server')->get('uniqid', $uniqid)->first(true);
		return new \cloudy\encryption\Server($uniqid, $s->pubKey);
	}
	
	public function pack($target, $msg) {
		
		$body = [
			'source'    => $this->uniqid,
			'target'    => $target,
			'time'      => time(),
			'msg'       => $msg,
			'signature' => base64_encode($this->priv->sign(sprintf('%s:%s:%s:%s', $this->uniqid, $target, time(), $msg)))
		];
		
		return json_encode($body);
	}
	
	public function unpack($msg) {
		$body = is_string($msg)? json_decode($msg) : $msg;
		
		if ($body->target !== $this->uniqid) {
			throw new PrivateException('This message was not intended for this server' . $body->target .  ' - ' . $this->uniqid, 1808241101);
		}
		
		if ($body->time < time() - 3600) {
			throw new PrivateException('Received expired message', 1808241102);
		}
		
		$remote = $this->server($body->source);
		
		if (!$remote->verify(sprintf('%s:%s:%s:%s', $body->source, $this->uniqid, time(), $body->msg), base64_decode($body->signature))) {
			throw new PrivateException('Invalid signature received', 1808241103);
		}
		
		return $body->msg;
	}

	public function generate() {
		/*
		 * Define the basic settings for the key being generated.
		 */
		$settings = openssl_pkey_new(array(
			'private_key_bits' => 4096,
			'private_key_type' => OPENSSL_KEYTYPE_RSA
		));
		
		openssl_pkey_export($settings, $private);
		
		$details = openssl_pkey_get_details($settings);
		$public  = $details['key'];
		
		return [$private, $public];
	}
}