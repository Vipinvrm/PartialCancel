<?php
namespace Vendor\PartialCancel\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class CancelItem extends Action implements HttpGetActionInterface
{
    protected $orderRepository;
    protected $messageManager;
    protected $customerSession;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $messageManager,
        CustomerSession $customerSession
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        $orderId = (int)$this->getRequest()->getParam('order_id');
        $itemId = (int)$this->getRequest()->getParam('item_id');

        try {
            $order = $this->orderRepository->get($orderId);

            if ($order->getCustomerId() != $this->customerSession->getCustomerId()) {
                throw new \Exception(__('You are not authorized to cancel this item.'));
            }

            $item = $order->getItemById($itemId);

            if (!$item) {
                throw new \Exception(__('Item not found.'));
            }

            if ($item->getQtyInvoiced() > 0 || $item->getQtyShipped() > 0) {
                throw new \Exception(__('This item cannot be canceled as it is already invoiced or shipped.'));
            }

            // Cancel only this item's quantity
            $item->setQtyCanceled($item->getQtyOrdered());

            // Adjust order totals: reduce subtotal and grand total by the row total for the canceled qty
            $rowTotal = $item->getRowTotal();
            $baseRowTotal = $item->getBaseRowTotal();

            $order->setSubtotal($order->getSubtotal() - $rowTotal);
            $order->setBaseSubtotal($order->getBaseSubtotal() - $baseRowTotal);
            $order->setGrandTotal($order->getGrandTotal() - $rowTotal);
            $order->setBaseGrandTotal($order->getBaseGrandTotal() - $baseRowTotal);

            $order->addStatusHistoryComment(__('Customer canceled item: %1', $item->getName()));
            $this->orderRepository->save($order);

            $this->messageManager->addSuccessMessage(__('Item canceled successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->_redirect('sales/order/view', ['order_id' => $orderId]);
    }
}
