-- Test a oprava databáze pro objednávky

-- Zkontrolovat, zda tabulka orders má sloupec note
-- Pokud ne, přidat ho
ALTER TABLE orders ADD COLUMN IF NOT EXISTS note TEXT AFTER shipping_address;

-- Zkontrolovat strukturu tabulky
DESCRIBE orders;

-- Zkontrolovat strukturu tabulky order_items
DESCRIBE order_items;

-- Zobrazit všechny objednávky
SELECT * FROM orders ORDER BY created_at DESC LIMIT 5;
