Macopedia SupplyManager Extension
=====================
This extension simplifies supply management. It allows Supply manager to have better overview and management of supply requests made by his employees.

Facts
-----
- version: 1.0.1
- extension key: Macopedia_SupplyManager
- invitations employee <-> supply manager with confirmation,
- extra tab in my account to manage employees,
- additional information in my account dashboard about supply managers,
- e-mail notifications (now only invitation),
- customizable emails from Magento backend -> transactional emails,
- show list of pending request from employees,
- move item from employee wishlist to cart (all kind of product),
- unsellable or not configured properly product cannot be moved to cart,
- delete item form employee wishlist,
- added field "supply manager email" to registration form,
- supply manager can track who requested the ordered item,
- additional information in order/invoice/email who requested the product
- [extension on GitHub](https://github.com/macopedia/Macopedia_SupplyManager)
- [direct download link](http://connect.magentocommerce.com/community/get/Macopedia_SupplyManager-1.0.1.tgz)

Description
-----------
This extension simplifies supply management. It allows Supply manager to have better overview and management of supply requests made by his employees.

Supply manager can attach employees using their emails to his account and manage their supply requests.
Employees also can attach supply managers using their emails to their account. When supply manager/employee attach on their account, other side must approve the attachment.
After Supply manager and Employee will approve their attachment, Supply manager can see employee wishlist. Employees can make supply requests by simply adding desired product to wishlist and later their supply manager can decide what to order from the list.
When supply manager adds product from employee wishlist to cart, the product is removed from employee wishlist and product in cart is tagged with information about the employee who requested the item for later tracking.

Demo Store
----------
Store link: [http://supplymanager.livedemo.macopedia.co](http://supplymanager.livedemo.macopedia.co)

#### Pre created Employee users:
- username: employee0@example.com | password: password
- username: employee1@example.com | password: password
- username: employee2@example.com | password: password
- ...
- username: employee9@example.com | password: password

#### Pre created Supply manager users:
- username: supplymanager1@example.com | password: password
- username: supplymanager2@example.com | password: password
- username: supplymanager3@example.com | password: password

#### Magento support user:
- admin link: [http://supplymanager.livedemo.macopedia.co/admin](http://supplymanager.livedemo.macopedia.co/admin)
- username: support | password: password

Requirements
------------
- PHP >= 5.2.0
- Mage_Core
- Mage_Wishlist

Compatibility
-------------
- Magento >= 1.7

Installation Instructions
-------------------------
1. Install the extension via Magento Connect with the key shown above or copy all the files into your document root.
2. Clear the cache, logout from the admin panel and then login again.
3. Configure the extension under System - Configuration - Sales - Supply Manager. (Optional)

Uninstallation
--------------
1. Remove all extension files from your Magento installation

Support
-------
If you have any issues with this extension, open an issue on [GitHub](https://github.com/macopedia/Macopedia_SupplyManager/issues).

Contribution
------------
Any contribution is highly appreciated. The best way to contribute code is to open a [pull request on GitHub](https://help.github.com/articles/using-pull-requests).

Developers
---------
- Paweł Cieślik [@cieslix](https://twitter.com/cieslix)
- Dragan Atanasov [@drashmk](https://twitter.com/drashmk)

Licence
-------
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)

Copyright
---------
(c) 2014 Macopedia
