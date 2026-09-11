# Kompatybilność

## Środowisko uruchomieniowe

SDK wspiera PHP 8.1 lub nowszy, Composer 2 oraz rozszerzenie JSON. Zależności uruchomieniowe obejmują interfejsy PSR HTTP, Nyholm PSR-7 i komponent Symfony HttpClient; pełny framework Symfony nie jest wymagany.

Composer dopuszcza Symfony HttpClient `6.4`, `7.4` i `8.1`. Guzzle jest opcjonalny — należy zainstalować go osobno i jawnie wybrać `GuzzleHttpClientFactory`.

## Bazowa wersja API

Implementacja oraz referencja obejmują wszystkie 41 nieprzestarzałych operacji produkcyjnych z bazy kontraktu przechowywanej w repozytorium. Dwie przestarzałe operacje upstream celowo nie są obsługiwane. Wydanie 1.0.0 jest zapięte na lock produkcyjnego API 1.6.9; dokument Next jest tylko monitorowany i nie wchodzi do tej linii. Nie oznacza to gwarancji dostępności przyszłej wersji API ani obietnicy wydania nieopublikowanej wersji SDK.

Do rozwoju i kontrolowanych testów używaj Stage. Sekrety, tokeny, adresy, magazyny i dane organizacji dla Stage oraz Production muszą pozostać rozdzielone.
