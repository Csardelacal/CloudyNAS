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

/**
 * Buckets are a "namespace for media". As opposed to directories on a filesystem,
 * buckets are not recursive - this means that, inside a bucket you cannot group
 * or organize files by anything but their filename.
 * 
 * The intention is to allow an application to write big amounts of data and be
 * able to retrieve it with a simple API. This makes CloudyNAS more akin to a 
 * memcached server with permanent storage than to a filesystem.
 */
class BucketModel extends Model
{
	
	/**
	 * 
	 * @param Schema $schema
	 */
	public function definitions(Schema $schema) {
		/*
		 * Uniqid is the key that the systems use to identify the resource over the
		 * network. This means that while a cluster may have the database _id 3 and 
		 * 2 on two servers of the same network, the uniqid will be "8sdd8" for both.
		 * 
		 * This makes it also more convenient for users to work with who will have 
		 * an easier time discerning combinations of alphanumeric strings than a 
		 * series of similar looking numbers.
		 */
		$schema->uniqid = new StringField(36);
		$schema->name   = new StringField(100);
		
		$schema->replicas = new IntegerField(true);
		
		/*
		 * Every bucket can have up to two clusters assigned. This allows for moving
		 * buckets across clusters. Once a pool disavows a cluster for a certain
		 * bucket, it will be placed as secondary until all the files in it have 
		 * been moved to a new home.
		 * 
		 * While this _could_ have been a m-n relation, it's pretty clear that the
		 * performance hit the system takes from having even a secondary cluster 
		 * that provides reads while the new one is assimilating it's contents seems
		 * too big to warrant the possibility of them being spread even further.
		 */
		$schema->cluster = new Reference('cluster');
		$schema->secondaryCluster = new Reference('cluster');
		
		$schema->index($schema->uniqid);
	}
	
	public function size() {
		/*@var $servers \spitfire\core\Collection*/
		$servers = $this->cluster->servers->getQuery()->all();
		
		$sorted = $servers
		->filter(function ($e) { return $e->role & cloudy\Role::ROLE_SLAVE; })
		->sort(function ($a, $b) {
			return $a->size - $b->size;
		})->extract('size');
		
		$count    = $sorted->count();
		$replicas = $count > $this->replicas? $this->replicas : $count;
		
		for ($i = 1; $i < $replicas; $i++) {
			$sorted->shift();
		}
		
		for ($i = 1; $i < $replicas; $i++) {
			$sorted->push($sorted->rewind());
		}
		
		try {
			return $sorted->avg();
		}
		catch (\Exception$e) {
			return 0;
		}
	}

}
