# Generowana referencja klas PHP

Podstawowym źródłem wiedzy o użyciu SDK jest ręcznie utrzymywana [referencja operacji](./reference/README.md). Opisuje zakres, modele żądań, typ wyniku i ograniczenia każdej metody.

Dodatkową referencję klas, właściwości i sygnatur można wygenerować lokalnie:

```bash
composer docs-build
```

Wynik trafia do `build/docs/api`. Jest artefaktem budowania, nie jest commitowany i powinien być generowany z tej samej rewizji kodu, którą analizujesz.
