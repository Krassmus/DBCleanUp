<?php

class DBCleanUp extends StudIPPlugin implements SystemPlugin
{

    public function __construct()
    {
        parent::__construct();
        if ($GLOBALS['perm']->have_perm("root")) {
            $nav = new Navigation(_("Datenbank aufräumen"), PluginEngine::getURL($this, array(), "waste/index"));
            Navigation::addItem("/admin/config/cleanup", $nav);
        }
    }
}