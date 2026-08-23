# OAuth 2.0 i cykl życia tokenów

SDK obsługuje Authorization Code z PKCE, Client Credentials oraz rotację tokenów odświeżających. Token dostępu, token odświeżający, kod autoryzacyjny, sekret klienta, OAuth `state` i weryfikator PKCE są danymi uwierzytelniającymi. Nie umieszczaj ich w adresach URL, pamięci przeglądarki, wyjątkach, metrykach ani logach.

## Wybór przepływu i zakresów

Authorization Code z PKCE służy do integracji, której użytkownik nadaje dostęp do konta sprzedawcy. Client Credentials stosuj wyłącznie wtedy, gdy integracja działa na rzecz własnego sklepu i taki model autoryzacji jest dopuszczony. Żądaj najmniejszego wystarczającego zestawu uprawnień; `OAuthScope::all()` ułatwia lokalne testy, ale nie powinno być ustawieniem domyślnym w produkcji.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\OAuthScope;

$readOnlyScopes = [
    OAuthScope::OpenId,
    OAuthScope::CategoriesRead,
    OAuthScope::OffersRead,
    OAuthScope::OrdersRead,
];
```

## Authorization Code z PKCE

Utwórz żądanie po stronie serwera. Zapisz zwrócone `state`, `codeVerifier` i dokładne `redirectUri` w chronionym, krótkotrwałym stanie serwerowym. Powiąż rekord z sesją przeglądarki i pozwól wykorzystać go tylko raz.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\OAuthClient;
use DevLancer\VonHalsky\Auth\OAuthScope;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Http\SymfonyHttpClientFactory;

$environment = Environment::stage();
$http = SymfonyHttpClientFactory::create();
$oauth = new OAuthClient(
    $environment,
    $http->httpClient,
    $http->requestFactory,
    $http->streamFactory,
);

$authorizationRequest = $oauth->createAuthorizationRequest(
    clientId: 'client-id',
    redirectUri: 'https://app.example.invalid/oauth/callback',
    scopes: [OAuthScope::OpenId, OAuthScope::OrdersRead],
);

// Zapisz te wartości po stronie serwera przed przekierowaniem przeglądarki.
$authorizationUrl = $authorizationRequest->authorizationUrl;
$expectedState = $authorizationRequest->state;
$codeVerifier = $authorizationRequest->codeVerifier;
$expectedRedirectUri = $authorizationRequest->redirectUri;
```

Po powrocie użytkownika do callbacka najpierw obsłuż parametr błędu od dostawcy, następnie pobierz i jednorazowo zużyj zapisany rekord. Przed wymianą kodu sprawdź obie wartości callbacka:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\OAuthClient;

OAuthClient::assertAuthorizationCallback(
    expectedState: $expectedState,
    receivedState: $receivedState,
    expectedRedirectUri: $expectedRedirectUri,
    receivedRedirectUri: $receivedRedirectUri,
);

$tokens = $oauth->exchangeAuthorizationCode(
    clientId: $clientId,
    authorizationCode: $authorizationCode,
    redirectUri: $expectedRedirectUri,
    codeVerifier: $codeVerifier,
    clientSecret: $clientSecret, // Pomiń, jeśli zarejestrowany klient go nie wymaga.
);
```

SDK zawsze używa PKCE `S256` i wymaga HTTPS dla redirect URI; HTTP jest dozwolone jedynie dla lokalnego loopback URI. Routing, wygaśnięcie sesji, ochrona przed ponownym użyciem kodu, obsługa błędu callbacka i przypisanie tokenów do właściwego sprzedawcy należą do aplikacji.

## Client Credentials

Sekret klienta pobieraj z chronionego magazynu sekretów środowiska uruchomieniowego. Proces roboczy powinien otrzymać tylko niezbędne zakresy:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\OAuthScope;

$tokens = $oauth->requestClientCredentialsToken(
    clientId: $clientId,
    clientSecret: $clientSecret,
    scopes: [OAuthScope::OffersRead, OAuthScope::OrdersRead],
);
```

Nie wypisuj ani nie zapisuj całego `TokenSet` w logach aplikacji.

## Trwały zapis i odświeżanie

`TokenSet` jest niezmienny i zawiera token dostępu, opcjonalny token odświeżający, typ tokenu, faktycznie przyznane zakresy oraz czas odbioru. SDK udostępnia interfejs, a nie gotowy magazyn bazodanowy. Zaimplementuj `TokenStoreInterface` tak, aby `save()` atomowo zastępowało cały zestaw. Zapisanie wyłącznie nowego tokenu dostępu może zgubić obrócony token odświeżający.

W środowisku wieloprocesowym implementacja `LockInterface` musi korzystać ze wspólnej blokady obejmującej ten sam obszar co magazyn tokenów. `RefreshingTokenProvider` odczytuje token, a gdy pozostaje domyślnie najwyżej 30 sekund do wygaśnięcia: blokuje `TokenContext`, ponownie odczytuje zestaw, wykonuje jedno odświeżenie i atomowo zapisuje wynik.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\RefreshingTokenProvider;
use DevLancer\VonHalsky\Auth\SystemClock;
use DevLancer\VonHalsky\Auth\TokenContext;

$context = TokenContext::forEnvironment(
    environment: $environment,
    clientId: $clientId,
    subject: 'merchant-account-id',
    organizationId: 'organization-id',
);

$tokenStore->save($context, $tokens);

$tokenProvider = new RefreshingTokenProvider(
    context: $context,
    store: $tokenStore,
    lock: $lock,
    oauthClient: $oauth,
    clientSecret: $clientSecret,
    clock: new SystemClock(),
);
```

Jeśli odpowiedź odświeżenia nie zawiera nowego tokenu odświeżającego lub zakresów, SDK zachowuje wcześniejsze wartości. Jeśli nowe wartości są obecne, zastępują stare. Brak użytecznego tokenu odświeżającego powoduje `AuthenticationFlowException` i wymaga ponownego przeprowadzenia autoryzacji. `TokenContext::storageKey()` jest nieodwracalnym kluczem przestrzeni nazw, ale nie zastępuje kontroli dostępu.

Błędy przepływu OAuth są celowo zredagowanymi `AuthenticationFlowException`. Odpowiedź HTTP 401 z właściwego API jest natomiast `AuthenticationException`; warto rozróżniać te przypadki w monitoringu.
