<?php
require_once __DIR__ . '/includes/db.php';

try {
    $db = getDB();

    $sql = "CREATE TABLE IF NOT EXISTS finance_expenses (
      id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      expense_date      DATE          NOT NULL,
      amount            DECIMAL(12,2) NOT NULL,
      category_id       INT UNSIGNED  NOT NULL,   -- FK -> finance_accounts (Expense type)
      asset_account_id  INT UNSIGNED  NOT NULL,   -- FK -> finance_accounts (Asset type)
      description       VARCHAR(255)  NOT NULL,
      reference_no      VARCHAR(50),
      notes             TEXT,
      recorded_by       INT UNSIGNED  NULL,
      created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_fexp_category FOREIGN KEY (category_id) REFERENCES finance_accounts(id),
      CONSTRAINT fk_fexp_asset    FOREIGN KEY (asset_account_id) REFERENCES finance_accounts(id),
      CONSTRAINT fk_fexp_admin    FOREIGN KEY (recorded_by) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;";

    $db->exec($sql);
    echo "finance_expenses table created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
