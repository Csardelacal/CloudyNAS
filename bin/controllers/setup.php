<?php

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
 * The configuration of any cloudy instance should be really barebones. Once the
 * server has a configuration file that tells it where the SSO server is and which
 * database to use, it can quickly and effectively start receiving tasks.
 * 
 * Tasks can be:
 * * Uploads
 * * Deletions
 * * Cron automated tasks (like discovery, health check, etc)
 */
class SetupController extends BaseController
{
	
	/**
	 * 
	 */
	public function index() {
		
		#TODO: Check whether the pool is already set up.
		#TODO: Include authentication. Only sysadmins should be able to set the system up
	}
	
	public function run() {
		
		#TODO: Check whether the pool is already set up.
		#TODO: Include authentication. Only sysadmins should be able to set the system up
		#TODO: Create the pool ID. So servers know when they're receiving faulty commands
		#TODO: Set the server role to pool and pool owner.
		
		/*
		 * This should be all. Once the server has been set up to be a pool owner we
		 * can redirect the user to the dashboard where they can either add other
		 * servers to the pool or assign additional roles to this server.
		 * 
		 * The template for this will display a success message and link the user 
		 * over to the dashboard page.
		 */
	}
	
}
