-- Generate or rotate the shared token used by office Windows agents.
-- Run this in phpMyAdmin after selecting the IT asset database.

SET @agent_token = CONCAT(
  'uia_',
  LOWER(REPLACE(UUID(), '-', '')),
  LOWER(REPLACE(UUID(), '-', ''))
);

INSERT INTO `agent_api_token`
  (`token_name`, `token_hash`, `active`, `expires_at`, `last_used_at`)
VALUES
  ('office-pcs', SHA2(@agent_token, 256), 1, NULL, NULL)
ON DUPLICATE KEY UPDATE
  `token_hash` = VALUES(`token_hash`),
  `active` = 1,
  `expires_at` = NULL,
  `last_used_at` = NULL;

SELECT
  SUBSTRING(@agent_token, 1, 17) AS `TOKEN_PART_1`,
  SUBSTRING(@agent_token, 18, 17) AS `TOKEN_PART_2`,
  SUBSTRING(@agent_token, 35, 17) AS `TOKEN_PART_3`,
  SUBSTRING(@agent_token, 52, 17) AS `TOKEN_PART_4`,
  CHAR_LENGTH(@agent_token) AS `TOKEN_LENGTH_MUST_BE_68`,
  'The previous office-pcs token is no longer valid.' AS `IMPORTANT`;

SET @agent_token = NULL;
