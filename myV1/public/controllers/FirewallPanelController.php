<?php


class FirewallPanelController extends Sirah_Controller_Default
{
    /**
     * The entry point of the Firewall Panel.
     */
    public function panelAction()
    {
		echo "Tests"; die();
        $panel = new \Shieldon\Firewall\Panel();
        $panel->entry();
		exit;
    }
}

 