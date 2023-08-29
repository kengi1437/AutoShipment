<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Eastz\AutoShipment\Controller\Shipany;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data as JsonHelperData;
use Psr\Log\LoggerInterface;

/**
 * Order Status Update Webhook controller.
 */
class Notify extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JsonHelperData
     */
    protected $jsonHelper;

    /**
     * @param Context $context
     * @param JsonHelperData $dataPersistor
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonHelperData $jsonHelper,
        LoggerInterface $logger = null
    ) {
        parent::__construct($context);
        $this->context = $context;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $success = true;

        try {

            $content = $this->getRequest()->getContent();
//            debug_time('Notify ' . $content);

            $result = json_decode($content, true);
            if (isset($result['results']) && is_array($result['results'])) {
                foreach ($result['results'] as $item) {
                    debug_time('Notify ' . $item['ext_order_ref']);
                    debug_time('Notify ' . $item['trk_no']);
                }
            }

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $service = $objectManager->get(\Eastz\AutoShipment\Model\Service\OrderService::class);
            $service->processShipanyResult($result);

        } catch (\Exception $e) {
            // throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            // {"result":{"code":400,"details":["Put your error messages here"]}}
            // $success = false;
        }

        $jsonResponse = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $jsonResponse->setData([
            'result' => ['code' => $success ? 200 : 400],
        ]);
        return $jsonResponse;
    }

    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ? InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
