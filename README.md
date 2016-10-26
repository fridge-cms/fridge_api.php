# Fridge API

## Install

Composer.json

```json
{
    "require": {
      "fridge/api": "@stable"
    }
}
```

## Usage

```php
$client = new \FridgeApi\Client("sk_xxxxxxxxxxx", "xxxxxxxxxxxx");
$pages = $client->get('content', array(
  'type' => 'pages'
));

foreach ($pages as $page) {
  $page->title = "New Page Title";
  // Save new title
  $client->put("content/{$page->id}", $page->commit());
}
```

See the [Fridge Documentation](https://fridgecms.com/docs/) for more examples.
