-- Migration: stocker la dernière lecture par ticket et par utilisateur
CREATE TABLE IF NOT EXISTS ticket_message_reads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  user_id INT NOT NULL,
  last_read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ux_ticket_user (ticket_id, user_id),
  INDEX idx_user (user_id),
  INDEX idx_ticket (ticket_id),
  CONSTRAINT fk_tmr_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);
