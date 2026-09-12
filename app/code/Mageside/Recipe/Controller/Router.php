<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Controller;

use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\Action\Redirect;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;


/**
 * Class Router
 */
class Router implements \Magento\Framework\App\RouterInterface
{
    /**
     * @var \Magento\Framework\App\ActionFactory
     */
    private $actionFactory;

    /**
     * @var HttpResponse
     */
    protected $response;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var \Mageside\Recipe\Model\WriterFactory
     */
    private $writerFactory;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Writer\CollectionFactory
     */
    private $writerCollectionFactory;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\Collection
     */
    private $recipeCollectionFactory;

    /**
     * @var \Mageside\Recipe\Model\RecipeFactory
     */
    private $recipeFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\CollectionFactory
     */
    private $filterCollectionFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\Mageside\Recipe\Helper\Config
     */
    private $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Router constructor.
     * @param \Magento\Framework\App\ActionFactory $actionFactory
     * @param \Mageside\Recipe\Model\WriterFactory $writerFactory
     * @param \Mageside\Recipe\Model\ResourceModel\Writer\CollectionFactory $writerCollectionFactory
     * @param \Mageside\Recipe\Model\RecipeFactory $recipeFactory
     * @param \Mageside\Recipe\Model\ResourceModel\Recipe\CollectionFactory $recipeCollectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\CollectionFactory $filterCollectionFactory
     * @param \Mageside\Recipe\Helper\Config $helper
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param UrlInterface $url
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\ActionFactory $actionFactory,
        \Mageside\Recipe\Model\WriterFactory $writerFactory,
        \Mageside\Recipe\Model\ResourceModel\Writer\CollectionFactory $writerCollectionFactory,
        \Mageside\Recipe\Model\RecipeFactory $recipeFactory,
        \Mageside\Recipe\Model\ResourceModel\Recipe\CollectionFactory $recipeCollectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Mageside\Recipe\Model\ResourceModel\Recipe\Filter\CollectionFactory $filterCollectionFactory,
        \Mageside\Recipe\Helper\Config $helper,
        \Magento\Framework\App\ResponseInterface $response,
        UrlInterface $url,
        StoreManagerInterface $storeManager
    ) {
        $this->writerFactory = $writerFactory;
        $this->writerCollectionFactory = $writerCollectionFactory;
        $this->recipeCollectionFactory = $recipeCollectionFactory;
        $this->actionFactory = $actionFactory;
        $this->recipeFactory = $recipeFactory;
        $this->coreRegistry = $coreRegistry;
        $this->filterCollectionFactory = $filterCollectionFactory;
        $this->helper = $helper;
        $this->response = $response;
        $this->url = $url;
        $this->storeManager = $storeManager;
    }

    /**
     * @param RequestInterface $request
     * @return ActionInterface|null|bool
     */
    public function match(RequestInterface $request)
    {
        $path = trim($request->getPathInfo(), '/');
        $p = explode('/', $path);
        $route = $this->helper->getSeoRoute();

        if (!isset($p[0]) || $p[0] != $route || count($p) > 2) {
            return null;
        }

        if (!isset($p[1])) {
            $request->setPathInfo('recipe/recipe/listview');
            $request->setAlias(UrlInterface::REWRITE_REQUEST_PATH_ALIAS, $path);

            return $this->actionFactory->create(Forward::class);
        }

        $writer = $this->writerFactory->create();
        $writer->load($p[1], 'writer_url_key');

        $postfix = $this->helper->getSeoPostfix();
        $urlKey = $postfix ? str_replace($postfix, '', $p[1]) : $p[1];
        $recipe = $this->recipeFactory->create();
        $recipe->load($urlKey, 'url_key');

        if (($writer->getCustomerId() && $recipe->getRecipeId())
            || (!$writer->getCustomerId() && !$recipe->getRecipeId())
        ) {
            return null;
        }

        if (!empty($writer->getCustomerId())) {
            if (substr($request->getPathInfo(), -1) == '/') {
                $redirectUrl = $this->url->getUrl('', ['_direct' => $path, '_query' => $request->getParams()]);
                return $this->redirect($request, $redirectUrl, 301);
            }

            if (!$writer->getIsWriter()) {
                $redirectUrl = $this->url->getUrl($route);
                return $this->redirect($request, $redirectUrl, 301);
            } else {
                $request->setPathInfo('recipe/writer/view');
                $request->setAlias(UrlInterface::REWRITE_REQUEST_PATH_ALIAS, $path);
                $request->setParam('customer_id', $writer->getCustomerId());
                return $this->actionFactory->create(Forward::class);
            }
        }

        if (!empty($recipe->getRecipeId())) {
            if (substr($request->getPathInfo(), -1) == '/') {
                $redirectUrl = $this->url->getUrl('', ['_direct' => $path, '_query' => $request->getParams()]);
                return $this->redirect($request, $redirectUrl, 301);
            }

            if (!$recipe->getStatus()) {
                $redirectUrl = $this->url->getUrl($route);
                return $this->redirect($request, $redirectUrl, 301);
            } else {
                $request->setPathInfo('recipe/recipe/view');
                $request->setAlias(UrlInterface::REWRITE_REQUEST_PATH_ALIAS, $path);
                $request->setParam('recipe_id', $recipe->getRecipeId());
                return $this->actionFactory->create(Forward::class);
            }
        }

        return null;
    }

    /**
     * Redirect to target URL
     *
     * @param RequestInterface|HttpRequest $request
     * @param string $url
     * @param int $code
     * @return ActionInterface
     */
    protected function redirect($request, $url, $code)
    {
        $this->response->setRedirect($url, $code);
        $request->setDispatched(true);

        return $this->actionFactory->create(Redirect::class);
    }
}
