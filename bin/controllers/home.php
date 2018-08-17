<?php

use cloudy\Role;
use spitfire\core\Environment;

/**
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class HomeController extends BaseController
{
	public function index() {
		
		/*
		 * Check whether the server is a pool, and if it is not... Either redirect
		 * the server to the known pool owner, or ask the user whether they wish
		 * to escalate this server to pool.
		 */
		if (!($this->settings->read('role') & Role::ROLE_POOL)) {
			
			/*
			 * Look for a pool manager. Usually, the pool setting contains the uniqid
			 * or hostname of the server. When this is empty we need to attach it 
			 * to a pool.
			 */
			if ($this->settings->read('poolid')) {
				$servers   = db()->table('server')->getAll()->all();
				$servers->each(function($e) {
					if ($e->role & Role::ROLE_OWNER) {
						return $this->response->setBody('Redirect...')->getHeaders()->redirect($poolowner->url);
					}
				});
			}
			
			/*
			 * The server does not know which pool it belongs to. It should offer 
			 * the owner the option to automatically attach it to a pool or to 
			 * upgrade this server to a pool.
			 */
			else {
				return $this->response->setBody('Redirection...')>getHeaders()->redirect(url('setup'));
			}
		}
		
		/*
		 * If this server is the pool server, then we allow it to present the 
		 * dashboard which allows monitoring of the system and data usage analysis.
		 * 
		 * The dashboard should also allow managing clusters, creating buckets, 
		 * and assigning servers and buckets to clusters.
		 */
		#TODO: Collect system information, show dashboard
	}
	
	public function cron() {
		$cron = new \cron\DiscoveryCron();
		$cron->run();
		die('Ok');
	}
	
	public function test() {
		
		$private = $this->settings->read('privkey');
		$public  = $this->settings->read('pubkey');
		
		$source  = 'This is a test';
		
		//openssl_private_encrypt($source, $crypted, $private);
		//openssl_public_decrypt($crypted, $message, $public);
		openssl_public_encrypt($source, $crypted, $public);
		openssl_private_decrypt($crypted, $message, $private);
		
		$this->view->set('message', 'Message: '  . $message);
	}
}