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
	
	/*
	 * The status constants. These allow the server to determine whether a node
	 * that requested being added to the network is allowed to participate in 
	 * the network.
	 * 
	 * Please note, that a pool server is the only server inside the network that
	 * should be permitted adding new servers to the network.
	 * 
	 * TODO: The pool server should sign all the servers which are part of the
	 * network to ensure that no server can manipulate the hive.
	 * 
	 * The problem is that the pool server would require the existence of a pool
	 * specific pair of keys, which the pool servers could share to sign the records.
	 */
	const STATUS_REJECTED = 0x000;
	const STATUS_PENDING  = 0x001;
	const STATUS_ACCEPTED = 0x002;
	
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
		
		$schema->cluster   = new StringField(100);
		$schema->size      = new FloatField(true);
		$schema->free      = new FloatField(true);
		
		$schema->status    = new IntegerField(true);
	}

}
