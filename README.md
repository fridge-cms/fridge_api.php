Fridge API

```php
$client = new \FridgeApi\Client("sk_xxxxxxxxxxx", "xxxxxxxxxxxx");
$pages = $api->get('content', array(
  'type' => 'pages'
));

foreach ($pages as $page) {
  $page->title = "New Page Title";
  // Save new title
  $api->put("content/{$page->id}", $page->commit());
}
```
