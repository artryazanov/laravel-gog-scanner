<?php

return [
    // Base URLs for GOG APIs
    'api_base'   => 'https://api.gog.com',
    'embed_base' => 'https://embed.gog.com',

    // Endpoints
    'list_endpoint'   => '/games/ajax/filtered',
    'detail_endpoint' => '/products/{id}',

    // Expand sections for product details
    'expand_fields'   => 'downloads,expanded_dlcs,description,screenshots,videos,related_products,changelog',

    // Default listing parameters
    'default_listing_params' => [
        'mediaType' => 'game',
        // 'limit' => 48,
    ],

    // HTTP timeout in seconds
    'http_timeout' => 30,

    // Queue options (can be overridden in the host app)
    'queue' => [
        'connection' => null, // null means default
        'queue'      => null, // null means default
    ],
];
