<?php
/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 *
 * @category   Shopware
 * @package    Shopware_Controllers
 * @subpackage Deprecated
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 * @version    $Id$
 * @author     Stefan Hamann
 * @author     $Author$
 */

/**
 * Shopware Backend Controller
 *
 * Display backend / administration
 */
class Shopware_Controllers_Backend_Deprecated extends Enlight_Controller_Action
{
    public function init()
    {
        $this->Front()->Plugins()->ScriptRenderer()->setRender();
    }

    public function preDispatch()
    {
        if(!in_array($this->Request()->getActionName(), array('index', 'load'))) {
            $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        }
    }

    /**
     * On index - get all Resources that we need in backend area
     */
    public function indexAction()
    {
    }

    /**
     * Load action for the script renderer.
     */
    public function loadAction()
    {
    }

    /**
     * Load action for the script renderer.
     */
    public function includeAction()
    {
        $oldPath = Shopware()->OldPath('engine');

        $module = basename($this->Request()->getParam('includeDir'));
        $module = preg_replace('/[^a-z0-9_.:-]/i', '', $module);
        if($module !== '') {
            $module .= '/';
        }
        $include = (string)$this->Request()->getParam('include', 'skeleton.php');
        $query = parse_url($include, PHP_URL_QUERY);
        $include = parse_url($include, PHP_URL_PATH);
        $include = preg_replace('/[^a-z0-9\\/\\\\_.:-]/i', '', $include);

        if(file_exists($oldPath . 'local_old/modules/' . $module . $include)) {
            $location = 'engine/local_old/modules/' . $module . $include;
        } elseif(file_exists($oldPath . 'backend/modules/' . $module . $include)) {
            $location = 'engine/backend/modules/' . $module . $include;
        }

        if(!empty($location)) {
            if(!empty($query)) {
                $location .= '?' . $query;
            } elseif($this->Request()->isPost()) {
                $location .= '?' . http_build_query($this->Request()->getPost(), '', '&');
            }
            $this->redirect($location);
        } else {
            $this->Response()->setHttpResponseCode(404);
        }
    }
}