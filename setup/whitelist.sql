DROP TABLE IF EXISTS 'whitelist_senders';
CREATE TABLE whitelist_senders (
  id INT AUTO_INCREMENT PRIMARY KEY,

  -- REQUIRED: domain scope (0 = global)
  domain_id INT NOT NULL DEFAULT 0,

  -- NULL = domain-wide rule
  -- non-NULL = per-user rule
  localpart VARCHAR(64) DEFAULT NULL,

  -- sender email (exact match)
  sender VARCHAR(255) NOT NULL,

  -- optional metadata (useful for debugging/admin UI)
  comment VARCHAR(255) DEFAULT NULL,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_domain (domain_id),
  INDEX idx_domain_localpart (domain_id, localpart),
  INDEX idx_sender (sender),
  INDEX idx_lookup (domain_id, localpart, sender)
);
