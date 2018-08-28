<?php namespace cloudy\helper;

use spitfire\storage\database\Table;

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

/**
 * This class should make it easy and straightforward to access and write the 
 * app's settings.
 * 
 * Data read from this class may be cached throughout the lifespan of the request,
 * if your application relies on the data being fresh from the database it will
 * be required to read it itself.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class SettingsHelper
{
	
	/**
	 * This model will be used to read the data from the database.
	 *
	 * @var Table
	 */
	private $table;
	
	/**
	 * 
	 * @param Table $table
	 */
	public function __construct($table) {
		$this->table = $table;
	}
	
	/**
	 * 
	 * @param string $key
	 * @return string
	 */
	public function read($key) {
		$query = $this->table->get('key', $key);
		$res   = $query->fetch();
		
		return $res? $res->value : null;
	}
	
	public function set($key, $value) {
		
		$query = $this->table->get('key', $key);
		$model = $query->fetch();
		
		if (!$model) {
			$model = $this->table->newRecord();
			$model->key = $key;
		}
		
		$model->value = $value;
		$model->store();
		
		return true;
	}
	
}