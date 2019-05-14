<?php

/**
 * Dime box Settlement Index Controller
 */

namespace Dimebox\Payment\Controller\Adminhtml\Settlements;

class Index extends \Magento\Backend\App\Action {

    protected $resultPageFactory = false;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
    \Magento\Backend\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute() {

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend((__('Dimebox Settlement Report')));

        return $resultPage;
    }

}
