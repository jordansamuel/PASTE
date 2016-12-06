DROP TABLE
  site_info;
DROP TABLE
  site_permissions;
DROP TABLE
  captcha;
CREATE TABLE site_info(
  id INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(id),
  title VARCHAR(255),
  des MEDIUMTEXT,
  keyword MEDIUMTEXT,
  site_name VARCHAR(255),
  email VARCHAR(255),
  twit VARCHAR(4000),
  face VARCHAR(4000),
  gplus VARCHAR(4000),
  ga VARCHAR(255),
  additional_scripts TEXT,
  baseurl TEXT
);
INSERT
INTO
  site_info(
    title,
    des,
    keyword,
    site_name,
    email,
    twit,
    face,
    gplus,
    ga,
    additional_scripts,
    baseurl
  )
VALUES(
  'Paste',
  'Paste can store text, source code or sensitive data for a set period of time.',
  'paste,pastebin.com,pastebin,text,paste,online paste',
  'Paste',
  '',
  'https://twitter.com/',
  'https://www.facebook.com/',
  'https://plus.google.com/',
  'UA-',
  '',
  ''
);
CREATE TABLE site_permissions(
  id INT(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(id),
  disableguest VARCHAR(255) DEFAULT NULL,
  siteprivate VARCHAR(255) DEFAULT NULL
);
INSERT
INTO
  site_permissions(id, disableguest, siteprivate)
VALUES(1, 'on', 'on'),(2, 'off', 'off');
CREATE TABLE captcha(
  id INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(id),
  cap_e VARCHAR(255),
  MODE VARCHAR(255),
  mul VARCHAR(255),
  allowed TEXT,
  color MEDIUMTEXT,
  recaptcha_sitekey TEXT,
  recaptcha_secretkey TEXT
);
INSERT
INTO
  captcha(
    cap_e,
    MODE,
    mul,
    allowed,
    color,
    recaptcha_sitekey,
    recaptcha_secretkey
  )
VALUES(
  'off',
  'Normal',
  'off',
  'ABCDEFGHIJKLMNOPQRSTUVYXYZabcdefghijklmnopqrstuvwxyz0123456789',
  '#000000',
  '',
  ''
)
