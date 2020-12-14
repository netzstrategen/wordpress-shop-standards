# Shop-Standards

This plugin adds what we consider standard refinements for e-commerce websites.
This refinements include diverse customizations to WooCommerce and related
plugins, like WooCommerce German Market.

## Configuration settings

The plugin adds several configuration settings in xx main points of the backend admin:
- [Custom tab in WooCommerce configuration page](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L15).
- Products options ([simple products](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L226), [products variations](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L473)).

## Shop customizations

The customizations applied by `shop-standards` affect diverse aspects of the way the shop works: products prices, products stocks, products delivery time, products listing filtering, SEO, performance, WP-CLI commands...

## WooCommerce customizations
These customizations include:
- [General products management and display](#general-products-management-and-display).
- [Products prices management and display](#products-prices)
- [Products sale label and category](#products-sale-label-and-category)
- [Products stocks management and display](#products-stocks)
- [Products delivery time management and display](#products-delivery-time)
- [Filtering products by delivery time](#filtering-products-by-delivery-time)
- [Plus Products category](plus-products-category)
- [Cart and checkout](#cart-and-checkout)

### General products management and display
- [Set number of products displayed on listing pages](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L188).
- [Minimum amount of product variations to trigger the AJAX retrieval of data](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L133).
- [Enable revisions for products descriptions](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L85).
- [Hide "Add to cart" button for products not to be sold online](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L195).
- [Hide product sale label](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L214). This is controlled by a backend [configuration setting](#configuration-settings).

#### Product backend custom fields

The plugin adds several backend custom fields to both simple products([#1](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerce.php#L226), [#2](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerce.php#L297), [#3](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerce.php#L313)) and variable products([#1](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerce.php#L473), [#2](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerce.php#L535)).

Custom fields added to products are:
- Simple products
  - Back in stock date
  - GTIN
  - ERP/Inventory ID
  - Display sale price as normal price (checkbox)
  - Product notes
  - Custom price label
  - Hide sale percentage bubble (checkbox)
  - Hide add to cart button (checkbox)
  - Variation has insufficient images (checkbox, only product variations)
  - Purchasing Price
  - Price comparison focus product

### Products prices
Customizations in the way the product prices are managed and displayed include:
- [Strike out regular prices range of variable products on sale](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L39).
- [Remove prefix "From" in variable products price ranges added by plugin B2B Market](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L75).
- [Display sale price as regular price](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L605). This is controlled by a backend [configuration setting](#configuration-settings).

### Products sale label and category
Products on sale can be automatically assigned a custom category. This is implemented in class [WooCommerceSaleLabel](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerceSaleLabel.php#L13).

### Products stocks
- [Display product status messages regarding availability](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L138).

### Products delivery time management and display
- [Update variable product delivery time with the lowest delivery time among its own variations](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/Admin.php#L77). The update is triggered [when the product is saved](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/Plugin.php#L92) or [manually using a WP-CLI custom command](#wp-cli-custom-commands).

This feature relies on a very specific way to setup the delivery time values. Those need to be defined as terms of taxonomy `product_delivery_times`.

The slug of each term in the taxonomy has to be a numeric value, so that the code is able to compare all values and select the lowest one. The recommended way is to set the slug of each term to the value in days of the corresponding delivery  time. If the delivery time is a range, we pick the highest time.

E.g.
- ca. 2-3 Wochen => 21
- ca. 5-6 Wochen => 40
- ca. 3-4 Werktage => 4
- 48 stunden => 2

### Filtering products by delivery time
This plugin adds a widget to filter products by their delivery time. This is achieved overriding layered nav WooCommerce widgets with new ones supporting WooCommerce German Market delivery time taxonomy terms ([see previous point](products-delivery-time-management-and-display)) to be used as product filters.

The whole implementation is contained in folder [src/ProductFilters](https://github.com/netzstrategen/wordpress-shop-standards/tree/master/src/ProductFilters), entry point being class [DeliveryTime](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/ProductFilters/DeliveryTime.php).

The time delivery product filter widget can be used in any products listing page, as any other WooCommerce (so called) layered navigation widget.

### Plus Products category
???

### Cart and checkout
- [Add salutation custom field for billing and shipping](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerceSalutation.php#L54). This is controlled by a backend [configuration setting](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerceSalutation.php#L33).
- [Add a confirmation email custom field on the checkout page](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerceCheckout.php#L66).This is controlled by a backend [configuration setting](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerceCheckout.php#L38).
- [Add basic information (e.g. weight, sku, etc.) and product attributes to cart item data](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerce.php#L673).

### Order emails
- [Add basic information (e.g. weight, sku, etc.) and product attributes to order emails](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerce.php#L725).

## Performance improvements

Performance improvements are mainly implemented in class [Performance](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/Performance.php). Those cover:
- [Preloading of main product image](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L410).
- Asynchronous loading of [styles](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/Performance.php#L84) and [scripts](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/Performance.php#L124).
- [DNS prefetching for resources](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/Performance.php#L147).
- [Prevent loading of unwanted assets](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/Performance.php#L59).

## Customizations on third part plugins

### WooCommerce German Market

- [Remove SKU from order item name](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerce.php#L778).
- [Fix delivery time is not displayed for variable products](https://github.com/netzstrategen/wordpress-shop-standards/blob/a15cb7d86afc6a7808cee6e9177a0c2c604e6119/src/WooCommerce.php#L969).

### B2B Market

- [Remove prefix "From" in variable products price ranges added by plugin B2B Market](https://github.com/netzstrategen/wordpress-shop-standards/blob/master/src/WooCommerce.php#L75).

## WP-CLI custom commands
Plugin `shop-standards` provide a custom WP-CLI command to update the delivery time of variable products. This command ensures the delivery time assigned to variable products is the lowest among its variations.

Although this update is triggered whenever the variable products are edited and saved, this offers a convenient way to update a given variable product (by ID), a list of them (comma separated list of IDs) or all of them.

__Update delivery time for a single product:__

```wp shop-standards refreshDeliveryTime 2165```

__Update delivery time for a list of products:__

```wp shop-standards refreshDeliveryTime 2165, 2166, 2167```

__Update delivery time for all products:__

```wp shop-standards refreshDeliveryTime --all```
