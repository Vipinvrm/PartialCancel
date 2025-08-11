<?php
namespace Vendor\PartialCancel\Model\GraphQL\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;

class CancelOrderItem implements ResolverInterface
{
    private $orderRepository;
    private $customerSession;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CustomerSession $customerSession
    ) {
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
    }

    public function resolve(
        $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $customerId = $context->getUserId();
        if (!$customerId) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $orderId = $args['order_id'] ?? null;
        $itemId = $args['item_id'] ?? null;

        if (!$orderId || !$itemId) {
            throw new GraphQlInputException(__('Order ID and Item ID are required.'));
        }

        try {
            $order = $this->orderRepository->get($orderId);
            if ($order->getCustomerId() != $customerId) {
                throw new GraphQlAuthorizationException(__('You are not authorized to cancel this item.'));
            }

            $item = $order->getItemById($itemId);
            if (!$item) {
                throw new GraphQlInputException(__('Item not found.'));
            }

            if ($item->getQtyInvoiced() > 0 || $item->getQtyShipped() > 0) {
                throw new LocalizedException(__('This item cannot be canceled as it is already invoiced or shipped.'));
            }

            $item->setQtyCanceled($item->getQtyOrdered());

            $rowTotal = $item->getRowTotal();
            $baseRowTotal = $item->getBaseRowTotal();

            $order->setSubtotal($order->getSubtotal() - $rowTotal);
            $order->setBaseSubtotal($order->getBaseSubtotal() - $baseRowTotal);
            $order->setGrandTotal($order->getGrandTotal() - $rowTotal);
            $order->setBaseGrandTotal($order->getBaseGrandTotal() - $baseRowTotal);

            $order->addStatusHistoryComment(__('Customer canceled item: %1', $item->getName()));
            $this->orderRepository->save($order);

            return ['success' => true, 'message' => __('Item canceled successfully.')];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
