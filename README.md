# EPOCA Autopublisher

**Versione:** 1.5
**Text Domain:** epoca-autopublisher

*EPOCA Autopublisher* è un plugin WordPress che ripubblica automaticamente il post più vecchio (in base alla data di pubblicazione originale) ogni ora, con criteri personalizzabili tramite interfaccia di amministrazione.

---

## Caratteristiche principali

* **Ripubblicazione oraria**: utilizza WP‑Cron per eseguire un evento personalizzato `epoca_republish_event` ogni ora.
* **Filtri dinamici**:

  * **Categorie**: seleziona quali categorie includere nella ripubblicazione.
  * **Parola filtro**: cerca una stringa nel titolo/contenuto e ripubblica i post che la contengono.
* **Anteprima backend**: nella pagina impostazioni, mostra il titolo e l’ID del prossimo post programmato per la ripubblicazione, con link diretto alla modifica.
* **Internazionalizzazione**: carica le traduzioni da `/languages` tramite `load_plugin_textdomain()` su hook `init`.

---

## Installazione

1. Copia la cartella `epoca-autopublisher` (contenente `epoca-autopublisher.php` e eventuali traduzioni in `/languages`) nella directory `wp-content/plugins/`.
2. Dal pannello di amministrazione di WordPress, vai su **Plugin → Plugin installati**.
3. Attiva **EPOCA Autopublisher**.
4. Dopo l’attivazione, troverai nel menu laterale **EPOCA Autopublisher**.

---

## Configurazione

1. Vai su **EPOCA Autopublisher** nel menu di amministrazione.
2. Nella schermata impostazioni:

   * Seleziona le **categorie** da includere.
   * Inserisci una **parola filtro** (opzionale).
   * Clicca su **Salva modifiche**.
3. Subito sopra il form, comparirà un **avviso** con l’anteprima del prossimo post che verrà ripubblicato.

---

## Utilizzo

* Ogni ora, WP‑Cron esegue `epoca_republish_event`:

  1. Recupera il post più vecchio che soddisfa i filtri impostati.
  2. Aggiorna la data di pubblicazione a quella corrente (`current_time('mysql')`).
* Se non ci sono post corrispondenti, l’evento termina senza azioni.

---

## Hook e Filtri

* **Hook di attivazione**: `register_activation_hook()` → pianifica l’evento cron.
* **Hook di disattivazione**: `register_deactivation_hook()` → rimuove l’evento.
* **Filtro `cron_schedules`**: aggiunge l’intervallo `epoca_hourly` (3600 s).
* **Azione `admin_notices`**: visualizza anteprima del prossimo post.

---

## Sviluppo e Traduzioni

* Carica il text domain nel metodo `load_textdomain()` (hook `init`).
* File POT disponibili in `/languages` per le traduzioni.

---

## Changelog

* **1.5**: Corretto caricamento textdomain, permessi pagina e hook `plugins_loaded`.
* **1.4**: Aggiornati callback `admin_notice_preview`.
* **1.3**: Standard WP 8.2 compliance.
* **1.2**: Risolto callback non riconosciuti.
* **1.1**: Aggiustati slug delle pagine impostazioni.
* **1.0**: Prima release.

---

## Licenza

GPL v2 o successiva
