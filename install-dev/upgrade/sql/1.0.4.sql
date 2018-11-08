SET NAMES 'utf8mb4';

# Update the referrer table to support the stronger tb hashes
ALTER TABLE `PREFIX_referrer` MODIFY `passwd` VARCHAR(60);
