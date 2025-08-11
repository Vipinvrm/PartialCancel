# Vendor_PartialCancel
Magento 2 module to allow customers to cancel individual order items from the frontend "My Orders" page.

## Installation
1. Upload the `Vendor/PartialCancel` folder to `app/code/Vendor/PartialCancel`.
2. Run:
   ```
   php bin/magento setup:upgrade
   php bin/magento cache:flush
   ```
3. Test as a customer: go to **My Account → My Orders → View Order** and click *Cancel Item* next to cancellable items.

## Notes & Warnings
- This module cancels items only when they are not invoiced or shipped.
- It adjusts order subtotal and grand total by removing the item's row total, but does not process payment refunds. For paid orders you should implement credit memos/refunds via the payment gateway.
- Use in production only after QA; it is a minimal implementation and may require extra checks (taxes, shipping adjustments, notifications).
