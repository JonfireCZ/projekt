-- SQL skript pro aktualizaci databáze (přidání sloupce note do tabulky orders)
-- Spustit pokud již máte tabulku orders vytvořenou bez sloupce note

-- Nejprve zkontrolujte, zda sloupec neexistuje:
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'orders' AND COLUMN_NAME = 'note';

-- Pokud sloupec neexistuje (query vrátí prázdný výsledek), spusťte:
ALTER TABLE orders ADD COLUMN note TEXT AFTER shipping_address;
