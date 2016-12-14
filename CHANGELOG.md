# Changelog for **[Paste](https://phpaste.sourceforge.io/)** (Updated on 14/12/2016)
New version - 2.1
-
Frontend changes
* User pages has been added and 'My Pastes' have been streamlined into this
* Ability to Fork or Edit pastes
* Raw view added
* Ability to embed pastes on websites
* Pastes can now be submitted and parsed as Markdown using **[Parsedown](http://parsedown.org/)**
* Added reCAPTCHA 2 support

Backend changes
* New options in the Admin panel in Configuration > Permissions

  Option to only allow registered users to paste
  
  Option to make site private, ie by disabling Recent Pastes and Archives
  
* New theme added: clean --- A white/grey version of the default theme
* New option in the Admin Panel in Configuration > Mail Settings to disable or enable email verification
* New option in the Admin panel in Configuration > Site Info to add javascript to the footer
* Added functionality in the Admin panel in >Pastes to ban IPs directly from the list
* Added functionality in the Admin panel in >Dashboard to compare the current installed version with the latest version

Other changes
* Code cleanup and elimination of errors

Previous version - 2.0
-

* New theme
* An installer
* User accounts added

  Ability to login and register with email verification
  
  'My Pastes' page with options to view and delete pastes

* Admin panel added

  Dashboard (front page) with a header to display some statistics of the day: overall views, unique views, pastes & users and lists to display recent pastes, users and admin logins
  
  Configuration page to apply Site name, title, description and keywords metatags, with sublinks to other configuration options such as Captcha settings (set the captcha type: easy, normal & tough and colour) and Mail settings for email verification (set Mail Protocol to either PHP Mail or SMTP and SMTP options)
  
  Interface page to set language with the new translations system, see /langs/ --- and also set the theme
  
  Admin account page to reset admin login details
  
  'Pastes' page to show a list of all pastes with options to delete and see more details
  
  'Users' page to show a list of all registered users with options to show if user registered with email or OAUTH and options to ban or delete
  
  'IP Bans' page to add and list IP bans
  
  'Statistics' page to show overall amount of pastes, expired pastes, users, banned users, page views & unique page views
  
  'Ads' page to add functionality to add ads to sidebar and footer sections
  
  'Pages' page to add new pages using a WYSIWIG editor, and also an option to view a list of pages with delete and edit functionality
  
  'Sitemap' page to control the frequency that the new sitemap system is updated
  
  'Tasks' page for some database optimization and common tasks, delete all expired pastes, clear admin history, delete unverified accounts 

* Archives added
* Captcha added

Other changes
* Overall code overhaul
