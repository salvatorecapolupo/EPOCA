# Agent Rules — EPOCA

> EPOCA - Plugin per WordPress di autopubblicazione vecchi post

## Codice
- Nomi descrittivi, no abbreviazioni crittiche
- Docstring su ogni funzione pubblica
- Type hints in Python, Javadoc in Java
- Nessun magic number senza costante nominata

## Testing
- Unit test obbligatori per ogni funzione pubblica
- Nessuna modifica senza test di regressione

## Sicurezza
- Zero segreti nel codice — usa variabili d'ambiente
- Nessuna chiave API, password o token in chiaro

## Git
- Conventional Commits: feat:, fix:, docs:, refactor:, test:, chore:
- Commit atomici, un concetto per commit
- Nessun file generato o .env nel repository

## Lingua
- Commenti e docstring in italiano
- Messaggi di commit in inglese
