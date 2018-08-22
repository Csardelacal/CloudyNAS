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
	
	private $pub;
	private $priv;
	
	public function __construct($public = null, $private = null) {
		$this->priv = $private;
		$this->pub = $public;
	}
	
	public function encodeWithPublic($msg) {
		$success = openssl_public_encrypt($msg, $result, $this->pub);
		
		if (!$success) {
			throw new PrivateException('Error creating cyphered message', 1808172004);
		}
		
		return $result;
	}
	
	public function decodeWithPublic($msg) {
		$success = openssl_public_decrypt($msg, $result, $this->pub);
		
		if (!$success) {
			throw new PrivateException('Error creating cyphered message: ' . openssl_error_string(), 1808172005);
		}
		
		return $result;
	}
	
	public function encodeWithPrivate($msg) {
		$success = openssl_private_encrypt($msg, $result, $this->priv);
		
		if (!$success) {
			throw new PrivateException('Error creating cyphered message', 1808172006);
		}
		
		return $result;
	}
	
	public function decodeWithPrivate($msg) {
		$success = openssl_private_decrypt($msg, $result, $this->priv);
		
		if (!$success) {
			throw new PrivateException('Error creating cyphered message', 1808172007);
		}
		
		return $result;
	}

	public function generate() {
		/*
		 * Define the basic settings for the key being generated.
		 */
		$settings = openssl_pkey_new(array(
			'private_key_bits' => 2048,
			'private_key_type' => OPENSSL_KEYTYPE_RSA
		));
		
		openssl_pkey_export($settings, $private);
		
		$details = openssl_pkey_get_details($settings);
		$public  = $details['key'];
		
		return [$private, $public];
	}
}