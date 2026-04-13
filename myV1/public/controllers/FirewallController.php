<?php


class FirewallController extends Sirah_Controller_Default
{
    
	
	
	public function indexAction()
	{
 
        $panel = new \Shieldon\Firewall\Panel();
        $panel->entry();
		exit;
 
	}
}

 