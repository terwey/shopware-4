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
 * @subpackage Site
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 * @version    $Id$
 * @author    Fabian Engels
 * @author     $Author$
 */

use DoctrineExtensions\Paginate\Paginate;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Shopware Site Controller
 *
 * The site backend controller handles all actions concerning the Site backend module
 */
use Shopware\Models\Site\Site as Site,
    Doctrine\ORM\AbstractQuery;

class Shopware_Controllers_Backend_Site extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Entity Manager
     * @var null
     */
    protected $manager = null;

    /**
     * @var \Shopware\Models\Site\Repository
     */
    protected $siteRepository = null;

    /**
     * Helper function to get access to the site repository.
     * @return \Shopware\Models\Site\Repository
     */
    private function getSiteRepository()
    {
        if ($this->siteRepository === null) {
            $this->siteRepository = Shopware()->Models()->getRepository('Shopware\Models\Site\Site');
        }
        return $this->siteRepository;
    }

    /**
     * Internal helper function to get access to the entity manager.
     * @return Shopware\Components\Model\ModelManager
     */
    private function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }
        return $this->manager;
    }

    /**
     * Registers the different acl permission for the different controller actions.
     *
     * @return void
     */
    protected function initAcl()
    {
        /**
         * permission to create a Group
         */
        $this->addAclPermission('createGroup', 'createGroup', 'Insufficient Permissions');

        /**
         * permission to delete a site
         */
        $this->addAclPermission('deleteSite', 'deleteSite', 'Insufficient Permissions');

        /**
         * permission to delete a group
         */
        $this->addAclPermission('deleteGroup', 'deleteGroup', 'Insufficient Permissions');

        /**
         * permission to get nodes / read
         */
        $this->addAclPermission('getNodes', 'read', 'Insufficient Permissions');

        /**
         * permission to create a site
         */
        $this->addAclPermission('saveSite', 'updateSite','Insufficient Permissions');
    }

    /**
     * required for creating the tree
     * takes a nodeName and creates all children for that particular node
     *
     * @return void
     */
    public function getNodesAction()
    {
        $node = $this->Request()->getParam('node');

        //create root nodes
        if ($node == 'root') {
            try {
                $query = $this->getSiteRepository()->getGroupListQuery();
                $sites = $query->getArrayResult();
                $this->View()->assign(array('success' => true, 'nodes' => $sites));
            } catch (Exception $e) {
                $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            }
        } else {
            try {
                //call the getSitesByNodeName helper function, which will return an array containing all children of that node
                $sites = $this->getSitesByNodeName($node);
                //hand that array to the view
                $this->View()->assign(array('success' => true, 'nodes' => $sites));
            } catch (Exception $e) {
                //catch all errors
                $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            }
        }

    }

    /**
     * helper function to build children of a node $nodeName
     *
     * @param $nodeName
     * @return array|bool
     */
    private function getSitesByNodeName($nodeName)
    {

        if (!empty($nodeName)) {

            $sites = $this->getSiteRepository()
                ->getSitesByNodeNameQuery($nodeName)
                ->getResult();

            $nodes = array();

            foreach ($sites as $site) {
                //call getSiteNode helper function to build the final array structure
                $nodes[] = $this->getSiteNode($nodeName . '_', $site);
            }
            return $nodes;
        } else {
            return false;
        }
    }

    /**
     * helper function to build the final array to be handed to the view
     *
     * @param $idPrefix
     * @param $site
     * @return array
     */
    private function getSiteNode($idPrefix, $site)
    {

        //set icons
        if ($site->getLink()) {
            $iconCls = 'sprite-chain-small';
        } else {
            $iconCls = 'sprite-blue-document-text';
        }

        //build the structure
        $node = array(
            'id' => $idPrefix . $site->getId(),
            'text' => $site->getDescription() . "(" . $site->getId() . ")",
            'description' => $site->getDescription(),
            'helperId' => $site->getId(),
            'tpl1variable' => $site->getTpl1Variable(),
            'tpl2variable' => $site->getTpl2Variable(),
            'tpl3variable' => $site->getTpl3Variable(),
            'tpl1path' => $site->getTpl1Path(),
            'tpl2path' => $site->getTpl2Path(),
            'tpl3path' => $site->getTpl3Path(),
            'grouping' => $site->getGrouping(),
            'position' => $site->getPosition(),
            'pageTitle' => $site->getPageTitle(),
            'metaKeywords' => $site->getMetaKeywords(),
            'metaDescription' => $site->getMetaDescription(),
            'html' => $site->getHtml(),
            'link' => $site->getLink(),
            'target' => $site->getTarget(),
            'leaf' => true,
            'iconCls' => $iconCls
        );

        //if the site has children, append them
        if ($site->getChildren()->count() > 0) {
            $children = array();
            foreach ($site->getChildren() as $child) {
                $children[] = $this->getSiteNode($idPrefix . $site->getId() . '_', $child);
            }
            $node['nodes'] = $children;
            $node['leaf'] = false;
        }
        return $node;
    }

    /**
     * this function enables the user to create groups
     * after taking a groupName and a templateVariable, it will check if either one already exists and if so, throw an exception
     * otherwise it will append the new group to cmsPositions in s_core_config
     */
    public function createGroupAction()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('Shopware\Models\Site\Group');
        $data = $this->Request()->getPost();
        $data = isset($data[0]) ? array_pop($data) : $data;

        $name = empty($data['groupName']) ? null : $data['groupName'];
        $key = empty($data['templateVar']) ? null : $data['templateVar'];

        if($key === null) {
            $this->View()->assign(array(
                'success' => false,
                'message' => 'Template Variable may not be empty'
            ));
            return;
        }

        if($name === null) {
            $this->View()->assign(array(
               'success' => false,
               'message' => 'Name may not be empty'
            ));
            return;
        }

        // Check if name exists
        $model = $repository->findOneBy(array('name' => $name));
        if($model !== null) {
            $this->View()->assign(array(
                   'success' => false,
                   'message' => 'nameExists'
            ));
            return;
        }

        // Check if key exists
        $model = $repository->findOneBy(array('key' => $key));
        if($model === null) {
            $model = new \Shopware\Models\Site\Group();
            $model->setKey($key);
        }else{
            $this->View()->assign(array(
               'success' => false,
               'message' => 'variableExists'
            ));
            return;
        }
        $model->setName($name);

        $manager->persist($model);
        $manager->flush();

        $this->View()->assign(array('success' => true));
    }

    /**
     * this function enables the user to delete groups
     * after taking the $templateVariable, it will get all groups and remove the requested group based on the tag
     * it will then update s_core_config accordingly
     * it will also move any orphans to the group gDisabled
     */
    public function deleteGroupAction()
    {
        $manager = $this->getManager();
        $repository = $manager->getRepository('Shopware\Models\Site\Group');

        $data = $this->Request()->getPost();
        $data = isset($data[0]) ? array_pop($data) : $data;

        $key = empty($data['templateVar']) ? null : $data['templateVar'];

        /** @var \Shopware\Models\Site\Group $model  */
        $model = $repository->findOneBy(array('key' => $key));
        if($model !== null) {
            $manager->remove($model);
            $manager->flush();
        }

        try {
            //first, get an array containing all sites id and grouping
            $sites = Shopware()->Db()->fetchAssoc("SELECT id,grouping FROM s_cms_static");

            //check is associated with the requested group
            //if so, either just delete it or, if the site would become an orphan, move it to the group gDisabled
            foreach ($sites as $site) {
                //try to explode into an array
                $groups = explode("|", $site['grouping']);

                //if we only have one group, exploding isn't possible, thus we create the array
                (sizeof($groups) == 1) ? $groups = array($site['grouping']) : null;

                //if the current site is associated with the requested group and has no other groups
                if (in_array($key, $groups) && sizeof($groups) == 1) {

                    //set group to gDisabled to prevent orphanage
                    Shopware()->Db()->query("UPDATE s_cms_static SET grouping = ? WHERE id = ?", array("gDisabled", $site['id']));
                } //if the current site is associated with the requested group and does have other associations
                else if (in_array($key, $groups) && sizeof($groups) > 1) {

                    //remove the requested group from the groupings field
                    str_replace($key, "", $site['grouping']);
                    str_replace("|", "", $site['grouping']);

                    //update the table
                    $sql = "UPDATE s_cms_static SET grouping = ? WHERE id = ?";
                    Shopware()->Db()->query($sql, array($site['grouping'], $site['id']));
                }
            }
            //success
            $this->View()->assign(array("success" => true));
        } catch (Exception $e) {
            //catch all errors
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
        }

    }

    /**
     * this function enables the user to delete a site
     * it will take the site id and simply remove it from the model
     * it will also set the parentID of all children of that site to zero
     */
    public function deleteSiteAction()
    {
        //get id
        $params = $this->Request()->getParams();
        $siteId = empty($params['siteId']) ? null : $params['siteId'];

        if (!empty($siteId)) {
            try {

                //remove site
                $model = $this->getSiteRepository()->find($siteId);
                $this->getManager()->remove($model);

                $this->getManager()->flush();

                //set the parentID of all children to 0
                //we don't want orphans
                $sql = "UPDATE s_cms_static SET parentID = 0 WHERE parentID = ?";
                Shopware()->Db()->query($sql, array($siteId));

                //hand siteId to view
                $this->View()->assign(array('success' => true, 'data' => $siteId));
            } catch (Exception $e) {
                //catch all errors
                $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
            }
        }
    }

    /**
     * this function enables the user to save or update a site
     * depending on whether or not a helperId is set,
     * it will either update a site with new values or create a new one, using the values provided
     */
    public function saveSiteAction()
    {
        //get the id from the helperId field
        $params = $this->Request()->getParams();
        $siteId = empty($params['helperId']) ? null : $params['helperId'];

        //this was a javascript array
        //change it back to the actual db format
        $params['grouping'] = str_replace(",", "|", $params['grouping']);

        //check whether we create a new site or are updating one
        //also, check if we have the necessary rights
        try {
            if (!empty($siteId)) {

                if (!$this->_isAllowed('updateSite', 'site')) {
                    $this->View()->assign(array('success' => false, 'message' => 'Permission denied.'));
                    return;
                }

                $site = $this->getSiteRepository()->find($siteId);
            } else {

                if (!$this->_isAllowed('createSite', 'site')) {
                    $this->View()->assign(array('success' => false, 'message' => 'Permission denied.'));
                    return;
                }

                $site = new Site();
            }

            $params['attribute'] = $params['attribute'][0];
            $site->fromArray($params);

            $this->getManager()->persist($site);

            $this->getManager()->flush();

            $data = $this->getSiteRepository()
                ->getSiteQuery($site->getId())
                ->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

            $this->View()->assign(array('success' => true, 'data' => $data));
        } catch (Exception $e) {
            //catch all errors
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    /**
     * Internal helper function to save the dynamic attributes of an site.
     * @param $site
     * @param $attributeData
     * @return mixed
     */
    private function saveSiteAttributes($site, $attributeData)
    {
        if (empty($attributeData)) {
            return;
        }
        if ($site->getId() > 0) {
            $result = $this->getSiteRepository()
                ->getAttributesQuery($site->getId())
                ->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT);
            if (empty($result)) {
                $attributes = new \Shopware\Models\Attribute\Site();
            } else {
                $attributes = $result;
            }
        } else {
            $attributes = new \Shopware\Models\Attribute\Site();
        }
        $attributes->fromArray($attributeData);
        $attributes->setSite($site);
        $this->getManager()->persist($attributes);
    }

    /**
     * builds an array containing all groups to be displayed in the itemSelectorField
     */
    public function getGroupsAction()
    {
        try {
            $grouping = $this->Request()->getParam('grouping');
            $grouping = explode('|', $grouping);

            $query = $this->getSiteRepository()->getGroupListQuery();
            $groups = $query->getArrayResult();

            foreach($groups as $groupKey => $group) {
                if (in_array($group['key'], $grouping)) {
                    unset($groups[$groupKey]);
                }
            }
            $groups = array_values($groups);

            $this->View()->assign(array('success' => true, 'groups' => $groups));
        } catch (Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    public function getSelectedGroupsAction()
    {
        try {
            $grouping = $this->Request()->getParam('grouping');
            $grouping = explode('|', $grouping);

            $query = $this->getSiteRepository()->getGroupListQuery();
            $groups = $query->getArrayResult();

            foreach($groups as $groupKey => $group) {
                if (!in_array($group['key'], $grouping)) {
                    unset($groups[$groupKey]);
                }
            }
            $groups = array_values($groups);

            $this->View()->assign(array('success' => true, 'groups' => $groups));
        } catch (Exception $e) {
            //catch all errors
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage()));
        }
    }
}
