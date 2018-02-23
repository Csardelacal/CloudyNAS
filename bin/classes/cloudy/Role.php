<?php namespace cloudy;


class Role
{
	
	/**
	 * The owner will manage several pools, this server will manage ALL the servers
	 * and make final decisions when several pool servers have conflicts.
	 * 
	 * NOTE: Servers pick the owner automatically. User definition of this role
	 * will be ignored.
	 */
	const ROLE_OWNER  = 0x20;
	
	/**
	 * The pool server manages the clusters and servers. It decides which servers
	 * are allocated to which cluster and which cluster is allocated to which
	 * bucket.
	 * 
	 * Every server in the network needs to "know" the pool manager (or the pool
	 * owner) to be associated with a task.
	 */
	const ROLE_POOL   = 0x10;
	
	/**
	 * The leader is a server in charge of resolving conflicts between several 
	 * masters. When a master sends another a command in order to synchronize 
	 * themselves and the receiving master detects it would cause a conflict, it
	 * will request the LEADER to resolve the issue.
	 * 
	 * For example, when a master receives a request to overwrite a file that was
	 * overwritten with a later timestamp, it will escalate the issue to the lead
	 * 
	 * NOTE: Servers pick the leader automatically. User definition of this role
	 * will be ignored.
	 */
	const ROLE_LEADER = 0x04;
	
	/**
	 * The master (or the masters) is a set of servers that will keep track of 
	 * the files on the cluster and an index of where to locate them across the
	 * slave network.
	 * 
	 * Masters will also be in charge of giving move commands to slaves. When a
	 * slave receives such a command, it will redirect the task automatically to
	 * it's preferred master.
	 */
	const ROLE_MASTER = 0x02;
	
	/**
	 * The slaves 'only' task is to contain the files that it receives from the 
	 * clients after the master has authorized their storage.
	 */
	const ROLE_SLAVE  = 0x01;
	
}
