# ez-srs — Easy Spaced Repetition

https://ez-srs.duckdns.org/index.php
<br>
A self-hosted, collaborative spaced repetition system (genuinley an Anki clone, but web based on PHP,JS,HTML and also includes collaborative features).
Users can create decks, share with others, propose edits to shared cards, and study using an
Anki-style scheduling algorithm

## Features
- User accounts (register/login) with per-user daily new-card limits
- Create and browse decks, public or private
- Cards support basic Q&A, cloze, no image types yet
- Deck collaboration: invite contributors as an editor
- Card proposals (git style): contributors can propose add/edit/delete changes to shared decks,
  which the owner can merge or reject
- Tagging system for cards
- Deck ratings/reviews
- Anki-style spaced repetition scheduler (ease factor, intervals, learning steps)
- Activity log (deck creation, studying, proposals, merges, etc.) in the database

## Tech stack
- PHP (mysqli) + MySQL/MariaDB
- Vanilla JS, HTML, CSS
- phpMyAdmin included for some reason (i f***ed up the server)
- PHP 8+ with the mysqli extension
- MySQL (i should use mariaDB)
- A web server (i use Apache on NixOS (holy larp))

## Status
school project for sofdev units 3&4, prolly abandon this soon

## License
MIT
