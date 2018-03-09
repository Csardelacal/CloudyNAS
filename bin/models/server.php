<?php

use spitfire\Model;
use spitfire\storage\database\Schema;

/**
 * This model allows the servers to manage a directory of their known peers.
 * This allows any server to authenticate themselves with each other in the 
 * event of them needing to communicate.
 * 
 * @property string $hostname The host name used to communicate with it
 * @property string $uniqid   ID the server uses to identify itself
 * @property string $pubKey   Server's public key
 * @property int    $role     Server's role
 */
class ServerModel extends Model
{
	
	/**
	 * 
	 * @param Schema $schema
	 */
	public function definitions(Schema $schema) {
		/*
		 * Variables for server identification. These allow servers to ensure that
		 * they're communicating with the appropriate counterparts when exchanging
		 * data.
		 */
		$schema->hostname = new StringField(250);
		$schema->uniqid   = new StringField(250);
		$schema->pubKey   = new StringField(4096);
		
		$schema->role     = new IntegerField(true);
		$schema->lastSeen = new IntegerField(true);
		
		$schema->diskUse  = new FloatField(true);
		$schema->diskSize = new FloatField(true);
	}

}
