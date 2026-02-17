UPDATE users SET password_hash = '$2y$10$0I7MFu1FK47iFLkUiH.8Me1RpF7D4iDnRhymYkGt17WD1W9e1UOk6' WHERE id = 1;
SELECT username, password_hash FROM users WHERE id = 1;
