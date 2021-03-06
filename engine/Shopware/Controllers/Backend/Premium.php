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
 * @subpackage Premium
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 * @version    $Id$
 * @author     Patrick Stahl
 * @author     $Author$
 */

/**
 * Shopware Premium Controller
 *
 * This controller handles all actions made by the user in the premium module.
 * It reads all premium-articles, creates new ones, edits and deletes them.
 * Additionally it also validates the form.
 */
class Shopware_Controllers_Backend_Premium extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var Shopware\Models\Premium\Repository
     */
    private $repository;


    /**
     * @var \Shopware\Components\Model\ModelRepository
     */
    protected $articleDetailRepository = null;

    /**
     * Helper function to get access to the articleDetail repository.
     * @return \Shopware\Components\Model\ModelRepository
     */
    private function getArticleDetailRepository() {
    	if ($this->articleDetailRepository === null) {
    		$this->articleDetailRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail');
    	}
    	return $this->articleDetailRepository;
    }

    public function initAcl()
    {
        $this->addAclPermission("getPremiumArticles", "read", "You're not allowed to see the articles.");
        $this->addAclPermission("createPremiumArticle", "create", "You're not allowed to create an article.");
        $this->addAclPermission("editPremiumArticle", "update", "You're not allowed to update the article.");
        $this->addAclPermission("deletePremiumArticle", "delete", "You're not allowed to delete the article.");
    }

    /**
     * Disable template engine for all actions
     *
     * @return void
     */
    public function preDispatch()
    {
        if (!in_array($this->Request()->getActionName(), array('index', 'load', 'validateArticle'))) {
            $this->Front()->Plugins()->Json()->setRenderer(true);
        }
    }

	public function getSubShopsAction(){
		//load shop repository
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');

		$builder = $repository->createQueryBuilder('shops');
		$builder->select(array(
            'shops.id as id',
            'shopLocale.id as locale',
            'category.id as categoryId',
            'shops.name as name'
        ));
        $builder->join('shops.category', 'category');
        $builder->leftJoin('shops.locale', 'shopLocale');
		$query = $builder->getQuery();

		//select all shops as array
		$data = $query->getArrayResult();

		//return the data and total count
		$this->View()->assign(array('success' => true, 'data' => $data));
	}

    /**
     * Function to get all premium-articles and it's name and subshop-name
     * Also used to enable the search of articles
     * @return void
     */
    public function getPremiumArticlesAction()
    {
		$this->repository = Shopware()->Models()->Premium();

		$start = $this->Request()->get('start');
		$limit = $this->Request()->get('limit');

		//order data
		$order = (array)$this->Request()->getParam('sort', array());

		//If a search-filter is set
		if ($this->Request()->get('filter')) {

			//Get the value itself
			$filter = $this->Request()->get('filter');
			$filter = $filter[count($filter) - 1];
			$filterValue = $filter['value'];

			$query = $this->repository->getBackendPremiumListQuery($start, $limit, $order, $filterValue);
			$totalResult = Shopware()->Models()->getQueryCount($query);
		} else {
			$query = $this->repository->getBackendPremiumListQuery($start, $limit, $order);
			$totalResult = Shopware()->Models()->getQueryCount($query);
		}

		try {
			$data = $query->getArrayResult();

			$this->View()->assign(array("success" => true, 'data' => $data, 'total' => $totalResult));
		}
		catch (Exception $e) {
			$this->View()->assign(array("success" => false, 'errorMsg' => $e->getMessage()));
		}
    }

    /**
     * Function to create a premium-article
     * @throws Exception
     * @return void
     */
    public function createPremiumArticleAction()
    {
        if (!$this->Request()->isPost()) {
            echo Zend_Json::encode(array('success' => false, 'errorMsg' => 'Empty Post Request'));
            return;
        }

        $params = $this->Request()->getParams();
		$params['startPrice'] = str_replace(",", ".", $params['startPrice']);
        $premiumModel = new Shopware\Models\Premium\Premium;

        try {
            if(empty($params['orderNumberExport'])){
                $params['orderNumberExport'] = $params['orderNumber'];
            }
			if(empty($params['orderNumber'])){
				throw new Exception("No ordernumber was entered.");
			}
            //Fills the model by using the array $params
            $premiumModel->fromArray($params);

            //find the shop-model by using the subShopId
            $shop = Shopware()->Models()->find('Shopware\Models\Shop\Shop', $params['shopId']);
            $premiumModel->setShop($shop);

            $articleDetail = $this->getArticleDetailRepository()->findOneBy(array('number' => $params['orderNumber']));
            $premiumModel->setArticleDetail($articleDetail);

            //If the article is already set as a premium-article
            /**
             * @var $repository Shopware\Models\Premium\Premium
             */
            $repository = Shopware()->Models()->getRepository('Shopware\Models\Premium\Premium');
            $result = $repository->findByOrderNumber($params['orderNumber']);
            $result = Shopware()->Models()->toArray($result);

            if (!empty($result) && $params['shopId'] == $result[0]['shopId']) {
                $this->View()->assign(array("success" => false, 'errorMsg' => "The article is already a premium-article."));
                return;
            }

            //saves the model
            Shopware()->Models()->persist($premiumModel);
            Shopware()->Models()->flush();

            $data = Shopware()->Models()->toArray($premiumModel);

            $this->View()->assign(array("success" => true, "data" => $data));
        }
        catch (Exception $e) {
            $this->View()->assign(array("success" => false, 'errorMsg' => $e->getMessage()));
        }
    }

    /**
     * Function to update a premium-article
     * @return void
     */
    public function editPremiumArticleAction()
    {
        $errorMsg = null;
        if (!$this->Request()->isPost()) {
            echo Zend_Json::encode(array('success' => false, 'errorMsg' => 'Empty Post Request'));
            return;
        }

        $params = $this->Request()->getParams();
        $premiumModel = Shopware()->Models()->find('Shopware\Models\Premium\Premium', $params['id']);

        try {
            if(empty($params['orderNumberExport'])){
                $params['orderNumberExport'] = $params['orderNumber'];
            }
            //Replace a comma with a dot
            $params['startPrice'] = str_replace(",", ".", $params['startPrice']);

            /**@var $premiumModel \Shopware\Models\Premium\Premium  */
            $premiumModel->fromArray($params);

            Shopware()->Models()->persist($premiumModel);
            Shopware()->Models()->flush();

            $this->View()->assign(array("success" => true, "data" => $params));
        }
        catch (Exception $e) {
            $this->View()->assign(array("success" => false, 'errorMsg', $e->getMessage()));
        }
    }

    /**
     * Function to delete a single or multiple premium-article(s)
     * @return void
     */
    public function deletePremiumArticleAction()
    {
		try {
			if (!$this->Request()->isPost()) {
				$this->View()->assign(array("success" => false, 'errorMsg' => 'Empty Post Request'));
				return;
			}
			$repository = Shopware()->Models()->Premium();

			$params = $this->Request()->getParams();
			unset($params['module']);
			unset($params['controller']);
			unset($params['action']);
			unset($params['_dc']);

			if($params[0]){
				$data = array();
				foreach($params as $values){
					$id = $values['id'];
					$model = $repository->find($id);
					Shopware()->Models()->remove($model);
					Shopware()->Models()->flush();
					$data[] = Shopware()->Models()->toArray($model);
				}
			}else{
				$id = $this->Request()->get('id');
				$model = $repository->find($id);

				Shopware()->Models()->remove($model);
				Shopware()->Models()->flush();
				$data = Shopware()->Models()->toArray($model);
			}
			$this->View()->assign(array("success" => true, 'data'=>$data));
        }
        catch (Exception $e) {
            $this->View()->assign(array("success" => false, 'errorMsg' => $e->getMessage()));
        }
    }

    /**
     * Function to check if an article exists or is already added as a premium-article
     * @return void
     */
    public function validateArticleAction()
    {
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
        $value = trim($this->Request()->get('value'));

        // Is there a value in the textfield?
        if ($value == null) {
            return;
        }

        //If the article exists
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail');
        $result = $repository->findByNumber($value);

        if (!$result[0]) {
            return;
        }

        //If the article is already set as a premium-article
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Premium\Premium');
        $result = $repository->findByOrderNumber($value);

        if ($result[0]) {
            return;
        }

        echo true;
    }
}