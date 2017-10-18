ALTER TABLE site_info ADD additional_scripts TEXT AFTER ga;
ALTER TABLE site_info ADD baseurl TEXT after additional_scripts;
ALTER TABLE captcha ADD recaptcha_sitekey TEXT after color;
ALTER TABLE captcha ADD recaptcha_secretkey TEXT after recaptcha_sitekey;
ALTER TABLE mail ADD verification TEXT NULL after id;
UPDATE mail set verification = "enabled";

CREATE TABLE site_permissions(
  id INT(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(id),
  disableguest VARCHAR(255) DEFAULT NULL,
  siteprivate VARCHAR(255) DEFAULT NULL
);
INSERT
INTO
  site_permissions(id, disableguest, siteprivate)
VALUES(1, 'on', 'on'),(2, 'off', 'off')
