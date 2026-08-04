<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Auth;

/** OAuth scopes documented for the Von Halsky API. */
enum OAuthScope: string
{
    case OpenId = 'openid';
    case CategoriesRead = 'api:categories:read';
    case OffersRead = 'api:offers:read';
    case OffersWrite = 'api:offers:write';
    case OrdersRead = 'api:orders:read';
    case OrdersWrite = 'api:orders:write';

    /** @return list<self> */
    public static function all(): array
    {
        return [
            self::OpenId,
            self::CategoriesRead,
            self::OffersRead,
            self::OffersWrite,
            self::OrdersRead,
            self::OrdersWrite,
        ];
    }
}
