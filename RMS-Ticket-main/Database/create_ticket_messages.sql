-- Migration: create table to store chat messages linked to tickets
CREATE TABLE IF NOT EXISTS ticket_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  sender_id INT NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (ticket_id),
  INDEX (sender_id),
  CONSTRAINT fk_tm_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);

-- Optionnel: ajouter FK vers users si table users existe
-- ALTER TABLE ticket_messages ADD CONSTRAINT fk_tm_user FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL;
