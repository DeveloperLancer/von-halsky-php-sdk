# Kompatybilność

## Środowisko uruchomieniowe

SDK wspiera PHP 8.1 lub nowszy, Composer 2 oraz rozszerzenie JSON. Zależności uruchomieniowe obejmują interfejsy PSR HTTP, Nyholm PSR-7 i komponent Symfony HttpClient; pełny framework Symfony nie jest wymagany.

Composer dopuszcza Symfony HttpClient `6.4`, `7.4` i `8.1`. Guzzle jest opcjonalny — należy zainstalować go osobno i jawnie wybrać `GuzzleHttpClientFactory`.

## Bazowa wersja API

Implementacja oraz referencja 42 operacji są zgodne z bazą kontraktu przechowywaną w repozytorium. Nie oznacza to gwarancji dostępności przyszłej wersji API ani obietnicy wydania nieopublikowanej wersji SDK.

Do rozwoju i kontrolowanych testów używaj Stage. Sekrety, tokeny, adresy, magazyny i dane organizacji dla Stage oraz Production muszą pozostać rozdzielone.
