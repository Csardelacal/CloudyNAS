<?php namespace cloudy;


class Role
{
	
	/**
	 * The pool server manages the clusters and servers. It decides which servers
	 * are allocated to which cluster and which cluster is allocated to which
	 * bucket.
	 * 
	 * Every server in the network needs to "know" the pool manager (or the pool
	 * owner) to be associated with a task.
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
