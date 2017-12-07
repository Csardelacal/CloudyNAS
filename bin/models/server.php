<?php

use spitfire\Model;
use spitfire\storage\database\Schema;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ServerModel extends Model
{
	
	/**
	 * 
	 * @param Schema $schema
	 */
	public function definitions(Schema $schema) {
		$schema->hostname = new StringField(250);
		$schema->pubKey   = new StringField(4096);
		$schema->lastSeen = new IntegerField(true);
	}

}
