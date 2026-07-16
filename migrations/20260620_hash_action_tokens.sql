UPDATE tokens
SET token = SHA2(token, 256)
WHERE token IS NOT NULL;

ALTER TABLE tokens
    CHANGE COLUMN token token_hash CHAR(64) DEFAULT NULL;
