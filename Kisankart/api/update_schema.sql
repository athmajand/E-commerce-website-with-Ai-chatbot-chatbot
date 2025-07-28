-- Update users table to add firstName and lastName fields
ALTER TABLE users
ADD COLUMN firstName VARCHAR(100) AFTER username,
ADD COLUMN lastName VARCHAR(100) AFTER firstName;

-- Update existing users to set firstName and lastName from username
UPDATE users
SET firstName = username, lastName = ''
WHERE firstName IS NULL OR lastName IS NULL;
