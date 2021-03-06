<?php

use spitfire\Model;
use spitfire\storage\database\Schema;

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

class FileModel extends Model
{
	
	/**
	 * 
	 * @param Schema $schema
	 */
	public function definitions(Schema $schema) {
		$schema->uniqid   = new StringField(36);
		
		/*
		 * Points to the revision of a piece of media that this file is hosting.
		 * 
		 * This field is only populated on masters, since slaves do not have any 
		 * notion of the relations between files and media. Therefore, when a 
		 * slave receives a request for a file it must either look up in a cache
		 * whether the file is available or look the information up on the master.
		 */
		$schema->revision = new Reference('revision');
		$schema->server   = new Reference('server');
		
		/*
		 * Indicates when the file expires (aka is no longer expected to be found
		 * on the server). When a client deletes a file, or overwrites it with a
		 * later version, this file is set to expire and will then be collected
		 * by the farbage collector at some later point. 
		 */
		$schema->expires  = new IntegerField(true);
		
		/*
		 * This flag indicates whether the remote server has properly accepted the
		 * file and tested whether it' works properly.'s checksum matches.
		 * 
		 * It's also set to 0 whenever a master writes changes that need to be
		 * commited back to the slaves.
		 */
		$schema->commited = new BooleanField();
		
		/*
		 * This fields are obviously only populated if the server is hosting the file
		 * itself.
		 */
		$schema->mime     = new StringField(30);
		$schema->checksum = new StringField(40);
		$schema->file     = new FileField();
		
		$schema->created  = new IntegerField(true);
	}
	
	public function onbeforesave() {
		if ($this->uniqid === null) {
			$this->uniqid = \cloudy\UUID::v4();
		}
		
		if (!$this->created) {
			$this->created = time();
		}
	}

}
