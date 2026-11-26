# Inventory skills test

A Laravel inventory page for recording product name, quantity in stock, and price per item. It uses Bootstrap and Ajax to add and edit entries in place. The table lists oldest submissions first in UTC and ends with the grand total.

## Run the release ZIP

1. Extract the archive. The release includes production Composer dependencies, local Bootstrap assets, and a configuration for the demo.
2. Point your PHP web server at the extracted `public/` directory. PHP 8.3+ on a 64-bit runtime and the standard Laravel PHP extensions are required.
3. Ensure the PHP worker can write to `storage/` and `bootstrap/cache/`. For a quick local preview, run `php artisan serve` from the extracted directory and open the address it prints.

For Apache shared hosting, you can also open the extracted folder directly when mod_rewrite and the included .htaccess are enabled. A public/ document root is the preferred server configuration.

The release starts with an empty inventory and uses no database, Node build, or CDN. The included application key is generated for the archive. Use a unique key for each installation beyond a local evaluation.

## Run from Git

Using PHP 8.3+ and Composer, run `composer install`, copy `.env.example` to `.env`, run `php artisan key:generate`, and then run `php artisan serve`. Use `php artisan test` for the automated checks.

## Data format

The application writes `storage/app/private/inventory.json`. Each row stores a UUID, the product name, a whole-number quantity, unit price in minor units, and an ISO 8601 submission time. Prices have two decimal places and the app stays currency-neutral. File locking and replacement protect the JSON file during writes. Edits keep the original submission time and recalculate the line and grand totals.

Bootstrap is bundled with its MIT license in `public/css/bootstrap-LICENSE.txt`.
