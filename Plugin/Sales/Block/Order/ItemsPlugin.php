<?php
namespace Vendor\PartialCancel\Plugin\Sales\Block\Order;

use Magento\Sales\Block\Order\Items as CoreItems;

class ItemsPlugin
{
    public function afterGetItemHtml(CoreItems $subject, $result, $item)
    {
        // If canceled
        if ((float)$item->getQtyCanceled() >= (float)$item->getQtyOrdered()) {
            $actionHtml = '<span style="color:red;">' . __('Canceled') . '</span>';
        }
        // If can cancel
        elseif ($item->getQtyInvoiced() == 0 && $item->getQtyShipped() == 0 && (float)$item->getQtyOrdered() > 0) {
            $cancelUrl = $subject->getUrl(
                'partialcancel/order/cancelitem',
                [
                    'order_id' => $item->getOrderId(),
                    'item_id' => $item->getItemId()
                ]
            );
            $actionHtml = '<a href="' . $cancelUrl . '" class="action cancel">' . __('Cancel') . '</a>';
        } else {
            $actionHtml = '';
        }

        if ($actionHtml) {
            // Append inside the last <td> in the HTML row
            $result = preg_replace('/(<\/td>\s*<\/tr>)/', '<br>' . $actionHtml . '$1', $result, 1);
        }

        return $result;
    }
}
