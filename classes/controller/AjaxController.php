<?php

class AjaxControllerCore extends ControllerCore
{
    /**
     * AjaxController constructor
     *
     * @global bool $useSSL SSL connection flag
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct()
    {
        $this->controller_type = 'ajax';

        global $useSSL;

        parent::__construct();

        if (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE')) {
            $this->ssl = true;
        }

        if (isset($useSSL)) {
            $this->ssl = $useSSL;
        } else {
            $useSSL = $this->ssl;
        }
    }

    /**
     * Check if the controller is available for the current user/visitor
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function checkAccess()
    {
        return true;
    }

    /**
     * Check if the current user/visitor has valid view permissions
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function viewAccess()
    {
        return true;
    }

    /**
     * Do the page treatment: process input, process AJAX, etc.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function postProcess()
    {
    }

    /**
     * Displays page view
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function display()
    {
    }

    /**
     * Sets default media list for this controller
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setMedia()
    {
    }

    /**
     * Assigns Smarty variables for the page header
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function initHeader()
    {
    }

    /**
     * Assigns Smarty variables for the page main content
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function initContent()
    {
        // TODO: Implement initContent() method.
    }

    /**
     * Assigns Smarty variables when access is forbidden
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function initCursedPage()
    {
    }

    /**
     * Ana ajax controller does not have a footer
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function initFooter()
    {
        return;
    }

    /**
     * An ajax controller should not redirect
     *
     * @see $redirect_after
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function redirect()
    {
    }
}
