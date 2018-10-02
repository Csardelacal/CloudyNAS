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
		$schema->lastCron = new IntegerField(true);
		$schema->queueLen = new IntegerField(true);
		
		/**
		 * Stores the cluster this server is assigned to. Please note that this does
		 * not allow for a master in one cluster to be a slave on another.
		 * 
		 * Generally servers should not have more than one role.
		 */
		$schema->cluster   = new Reference('cluster');
		$schema->size      = new FloatField(true);
		$schema->free      = new FloatField(true);
		$schema->writable  = new BooleanField();
		
		/*
		 * Record whether and when the server was decommissioned. This allows the 
		 * netowrk to register servers that left it by broadcasting the fact that
		 * they're gone and since when they are gone.
		 * 
		 * This also allows the server to stay around during a migration. While 
		 * disabled servers provide no read capacity, they can be used to read data.
		 * 
		 * Please note that, a disabled server should never be considered an 
		 * authoritative source. The intention is for them to be dismantled.
		 */
		$schema->disabled  = new IntegerField(true);
		
		/*
		 * This flag determines whether the server is running. An administrator may
		 * have requested the server to pause, in order to shut the server down,
		 * perform maintenance or do something similar.
		 */
		$schema->active    = new BooleanField();
	}
	
	public function getMaster() {
		return $this->getTable()->getDb()
			->table('server')
			->get('cluster', $this->cluster)
			->where('active', true)
			->all()
			->filter(function ($e) { return $e->role & cloudy\Role::ROLE_MASTER; })
			->rewind();
	}
	
	public function resolve($link, $revisionid = null) {
		
		/*
		 * In the special event that the server is, itself, the master and therefore
		 * does not require a request to resolve.
		 */
		if ($this->role & cloudy\Role::ROLE_MASTER) {
			
			$l = db()->table('link')->get('uniqid', $link)->first(true);
			$media = $l->media;
			
			$query = db()->table('revision')->get('media', $media);
			$revisionid? $query->where('uniqid', $revisionid)->group()->where('expires', null)->where('expires', '>', time()) : $query->where('expires', null);
			
			return db()->table('file')->get('revision', $query->first(true))->where('server', $this)->first();
		}
		
		/*
		 * IMPLICIT ELSE-
		 * 
		 * Since this task relies on the network, and links generally don't change
		 * too often, we use memcached to reduce the amount of requests and therefore
		 * the stress on the network.
		 */
		$memcached = new \spitfire\cache\MemcachedAdapter();
		
		$file = $memcached->get('link_' . $link . '_' . $revisionid, function () use ($link, $revisionid) {
			
			$master  = $this->getMaster();
			$request = request($revisionid? $master->hostname . '/link/read/' . $link . '/' . $revisionid . '.json' : $master->hostname . '/link/read/' . $link . '.json');
			
			/*
			 * TODO: I'm almost DISGUSTED by myself pushing the call to current_context()
			 * here and it should go ASAP. I'll look into refactoring this mess.
			 */
			$request->header('Content-type', 'application/json')
			->post(current_context()->controller->keys->pack($master->uniqid, base64_encode(random_bytes(150))));

			$files = $request->send()->expect(200)->json()->files;

			foreach ($files as $file) {
				if ($this->uniqid == $file->server) { return $file->uniqid; }
			}

		});

		return $this->getTable()->getDb()->table('file')->get('uniqid', $file)->first();
	}

}
