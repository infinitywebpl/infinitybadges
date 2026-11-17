# Etykiety produktów (infinitybadges) — wersja demo

Moduł PrestaShop 8.0–8.x (PHP ≥ 8.1). Automatycznie dodaje etykiety na zdjęciach produktów, np. `IP65`, `3000 K`, `Ø 60 cm` albo „Made in Poland”, według reguł, bez ręcznego przypisywania do produktów. Etykiety wchodzą jako flagi produktu (tam, gdzie motyw pokazuje „Nowość” czy „Promocja”), więc działa z każdym motywem bez zmian w szablonach.

**To wersja demo.** Działają etykiety z wartości cechy i teksty stałe, na wszystkich produktach, z własnymi stylami. Pozostałe funkcje widać w panelu, ale działają w pełnej wersji. Kontakt w sprawie pełnej wersji: https://www.infinityweb.pl/oferta/moduly-prestashop, biuro@infinityweb.pl

### [⬇ Pobierz demo (ZIP do wgrania w PrestaShop)](https://github.com/infinitywebpl/infinitybadges/releases/latest)

W wydaniu pobierz plik `infinitybadges-<wersja>-demo.zip`, a nie „Source code”: tylko on ma katalog, który PrestaShop zainstaluje.

Panel: **Katalog → Etykiety produktów** (albo **Moduły → Etykiety produktów (demo) → Konfiguruj**).

## Co działa w demie

| funkcja | demo | pełna wersja |
|---|---|---|
| Etykieta z cechy: wartość cechy produktu, prefiks i sufiks bez podwójnych jednostek, kilka wartości w jednej etykiecie | ✓ | ✓ |
| Etykieta z tekstem stałym | ✓ | ✓ |
| Style etykiet (kolory, krycie, czcionka, zaokrąglenie, obramowanie, odstępy, osobno desktop i mobile), podgląd i ostrzeżenie o kontraście | ✓ | ✓ |
| Priorytet, daty od–do, desktop/mobile, limit etykiet na produkt, bez duplikatów | ✓ | ✓ |
| Tłumaczenia tekstów etykiet z językiem domyślnym jako zastępstwem | ✓ | ✓ |
| Lista reguł: kolejność przeciąganiem, duplikowanie, włączanie i wyłączanie | ✓ | ✓ |
| Kryteria: grupy kategorii (z podkategoriami), producentów, dostawców, cech, ceny, obniżki i produktów, „Zawsze wykluczaj” | — | ✓ |
| Etykiety-obrazki (JPG, PNG, SVG) | — | ✓ |
| Znacznik `[discount]`, np. „OKAZJA -[discount]%” | — | ✓ |
| Nazwa cechy albo własny tekst zamiast wartości, ukrywane wartości (np. „Nie”) | — | ✓ |
| Ukrywanie flag PrestaShop (np. „-20%”) tam, gdzie jest etykieta | — | ✓ |
| Podgląd na produkcie z powodem każdej decyzji, produkty pasujące do reguły | — | ✓ |
| Eksport i import konfiguracji, tryb debug | — | ✓ |

W demie każda aktywna reguła dotyczy **wszystkich produktów**. Etykieta z cechy pojawia się tylko na produktach, które mają wartość tej cechy, więc np. reguła z cechy „Stopień ochrony” pokaże `IP65` albo `IP44` tylko tam, gdzie produkt ma tę cechę. Etykieta ze znacznikiem `[discount]` w demie się nie pokazuje.

## Pełna wersja: kryteria

```
Reguła „Made in Poland”
├─ Grupa 1: kategoria x lub y  I  producent z
├─ Grupa 2: kategoria m  I  NIE producent j
└─ Zawsze wykluczaj: kategoria „Żarówki” (z podkategoriami)
```

Etykieta pojawia się, gdy pasuje dowolna grupa, a w grupie muszą pasować wszystkie kryteria. „Zawsze wykluczaj” blokuje etykietę niezależnie od grup. Etykiety liczą się w locie, więc zmiana kategorii, producenta, cechy czy ceny produktu od razu zmienia etykiety, także po imporcie produktów.

## Instalacja i przejście na pełną wersję

- Pobierz ZIP z [najnowszego wydania](https://github.com/infinitywebpl/infinitybadges/releases/latest) i wgraj go w PrestaShop: **Moduły → Menedżer modułów → Wgraj moduł**.
- Instalacja tworzy tabele `PREFIX_infinitybadges_*`, rejestruje hooki i zakładkę **Katalog → Etykiety produktów**. Po instalacji nie ma reguł, więc na froncie nic się nie zmienia.
- Style frontu są w jednym wygenerowanym pliku w `views/css/generated/`, więc ten katalog musi być zapisywalny.
- **Odinstalowanie usuwa wszystkie reguły i style.**
- Przejście na pełną wersję: wgraj pliki pełnej wersji w miejsce dema (bez odinstalowywania). Reguły, style i ustawienia zostają.

## Licencja

Warunki korzystania z wersji demo: [LICENSE.md](LICENSE.md). Zakładka **„Licencja”** w panelu modułu pokazuje, co jest w wersji pełnej, i kontakt. Wersja demo jest bezpłatna, bez kluczy i bez sprawdzania licencji. Wersja pełna wymaga licencji na domenę sklepu: [biuro@infinityweb.pl](mailto:biuro@infinityweb.pl).

## Tłumaczenia

Teksty panelu są po angielsku, a polskie tłumaczenia w `translations/pl-PL/`.
